<?php

namespace App\Services;
use App\Models\DocumentChunk;
use App\Repositories\ConversationRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\VectorRepositoryInterface;
use App\Services\Contracts\EmbeddingProviderInterface;
use App\Services\Contracts\GenerationProviderInterface;
use App\Services\Contracts\RetrievalProviderInterface;

class RagService
{
    public function __construct(
        protected EmbeddingProviderInterface $embedder,
        protected RetrievalProviderInterface $retriever,
        protected GenerationProviderInterface $generator,
        protected DocumentRepository $documents,
        protected VectorRepositoryInterface $vectors,
        protected ConversationRepository $conversations
    ) {
    }

    public function ingestDocument(string $source, string $content, array $metadata = []): array
    {
        $document = $this->documents->createOrUpdate($source, $content, $metadata);
        $chunks = $this->chunkContent($content);

        foreach ($chunks as $index => $chunkText) {
            $chunk = DocumentChunk::create([
                'document_id' => $document['id'],
                'content' => $chunkText,
                'metadata' => array_merge($metadata, ['chunk_index' => $index]),
                'embedding' => $this->embedder->embed($chunkText),
            ]);

            $this->vectors->saveEmbedding($document['id'], $chunk->embedding, [
                'source' => $source,
                'document_id' => $document['id'],
                'chunk_id' => $chunk->id,
                'chunk_index' => $index,
            ]);
        }

        return $document;
    }

    protected function chunkContent(string $content, int $chunkSize = 512, int $overlap = 64): array
    {
        $content = trim(preg_replace('/\s+/', ' ', $content));

        if ($content === '') {
            return [];
        }

        $words = explode(' ', $content);
        $chunks = [];
        $current = [];
        $currentLength = 0;

        foreach ($words as $word) {
            $wordLength = mb_strlen($word) + 1;

            if ($currentLength + $wordLength > $chunkSize && count($current) > 0) {
                $chunks[] = implode(' ', $current);
                $current = array_slice($current, max(0, count($current) - $overlap));
                $currentLength = mb_strlen(implode(' ', $current));
            }

            $current[] = $word;
            $currentLength += $wordLength;
        }

        if (! empty($current)) {
            $chunks[] = implode(' ', $current);
        }

        return $chunks;
    }

    public function retrieve(string $query, int $limit = 5): array
    {
        return $this->retriever->search($query, $limit);
    }

    public function answer(string $query, ?string $sessionId = null, int $limit = null): array
    {
        $limit = $limit ?? config('rag.retrieval.top_k', 5);
        $documents = $this->retrieve($query, $limit);

        $prompt = $this->composePrompt($query, $documents, $sessionId);
        $answer = $this->generator->generate($prompt, [
            'query' => $query,
            'documents' => $documents,
        ]);

        if ($sessionId) {
            $this->conversations->appendMessage($sessionId, 'user', $query);
            $this->conversations->appendMessage($sessionId, 'assistant', $answer);
        }

        return [
            'answer' => $answer,
            'documents' => $documents,
            'session_id' => $sessionId,
        ];
    }

    protected function composePrompt(string $query, array $documents, ?string $sessionId = null): string
    {
        $system = config('rag.prompt.system');
        $context = collect($documents)
            ->map(fn ($doc, $index) => sprintf("[%d] %s\n%s", $index + 1, $doc['source'] ?? 'unknown', $doc['content']))
            ->join("\n\n");

        $history = '';

        if ($sessionId) {
            $messages = $this->conversations->getMessages($sessionId);
            if (!empty($messages)) {
                $history = collect($messages)
                    ->map(fn ($item) => sprintf("%s: %s", ucfirst($item['role']), $item['message']))
                    ->join("\n");
            }
        }

        return trim(<<<PROMPT
{$system}

Context:
{$context}

Conversation history:
{$history}

User question:
{$query}

Answer using only the context provided above. If the answer is not contained in the documents, say that you do not know.
PROMPT);
    }
}
