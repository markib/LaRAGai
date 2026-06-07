<?php

namespace App\Services;

use App\Models\DocumentChunk;
use App\Repositories\ConversationRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\VectorRepositoryInterface;
use App\Services\Contracts\EmbeddingProviderInterface;
use App\Services\Contracts\GenerationProviderInterface;
use App\Services\Contracts\RetrievalProviderInterface;
use Illuminate\Support\Str;

class RagService
{
    public function __construct(
        protected EmbeddingProviderInterface $embedder,
        protected RetrievalProviderInterface $retriever,
        protected GenerationProviderInterface $generator,
        protected DocumentRepository $documents,
        protected VectorRepositoryInterface $vectors,
        protected ConversationRepository $conversations
    ) {}

    /**
     * =========================
     * INGESTION PIPELINE
     * =========================
     */
    public function ingestDocument(
        string $source,
        string $content,
        array $metadata = []
    ): array {
        $document = $this->documents->createOrUpdate($source, $content, $metadata);

        $chunks = $this->chunkContent($content);

        foreach ($chunks as $index => $chunkText) {
            $embedding = $this->embedder->embed($chunkText);

            $chunk = DocumentChunk::create([
                'document_id' => $document['id'],
                'content' => $chunkText,
                'metadata' => array_merge($metadata, [
                    'chunk_index' => $index,
                    'source' => $source,
                ]),
                'embedding' => $embedding,
            ]);

            $this->vectors->saveEmbedding(
                $document['id'],
                $embedding,
                [
                    'source' => $source,
                    'document_id' => $document['id'],
                    'chunk_id' => $chunk->id,
                    'chunk_index' => $index,
                ]
            );
        }

        return $document;
    }

    /**
     * =========================
     * RETRIEVAL
     * =========================
     */
    public function retrieve(string $query, int $limit = 5): array
    {
        return $this->retriever->search($query, $limit);
    }

    /**
     * =========================
     * MAIN RAG ANSWER PIPELINE
     * =========================
     */
    public function answer(
        string $query,
        ?string $sessionId = null,
        int $limit = 5
    ): array {

        $documents = $this->retrieve($query, $limit);

        if (empty($documents)) {
            return [
                'answer' => "I couldn't find relevant information in your documents.",
                'documents' => [],
                'session_id' => $sessionId,
            ];
        }

        if (config('rag.retrieval.re_rank', true)) {
            $documents = $this->reRankDocuments($query, $documents);
        }

        $prompt = $this->composePrompt($query, $documents, $sessionId);

        $answer = $this->generator->generate($prompt, [
            'query' => $query,
            'documents' => $documents,
        ]);

        return [
            'answer' => $answer,
            'documents' => $documents,
            'session_id' => $sessionId,
        ];
    }

    /**
     * =========================
     * CHUNKING (PRODUCTION SAFE)
     * =========================
     */
    protected function chunkContent(
        string $content,
        int $chunkSize = 900,
        int $overlap = 150
    ): array {

        $content = preg_replace('/\s+/', ' ', trim($content));

        if ($content === '') {
            return [];
        }

        $chunks = [];
        $start = 0;
        $length = mb_strlen($content);

        while ($start < $length) {
            $chunks[] = mb_substr($content, $start, $chunkSize);
            $start += ($chunkSize - $overlap);
        }

        return array_values(array_filter($chunks));
    }

    /**
     * =========================
     * PROMPT BUILDER
     * =========================
     */
    protected function composePrompt(
        string $query,
        array $documents,
        ?string $sessionId = null
    ): string {

        $system = config('rag.prompt.system', 'You are a helpful assistant.');

        $context = collect($documents)
            ->map(function ($doc, $i) {
                return sprintf(
                    "[%d] Source: %s\n%s",
                    $i + 1,
                    $doc['source'] ?? 'unknown',
                    $doc['content'] ?? ''
                );
            })
            ->join("\n\n");

        $history = '';

        if ($sessionId) {
            $messages = $this->conversations->getMessages($sessionId);

            if (!empty($messages)) {
                $history = collect($messages)
                    ->take(10) // prevent token explosion
                    ->map(fn($m) => ucfirst($m['role']) . ': ' . $m['message'])
                    ->join("\n");
            }
        }

        return trim("
{$system}

Context:
{$context}

Conversation History:
{$history}

User Question:
{$query}

Instructions:
- Use ONLY the provided context
- If unsure, say you don't know
- Be concise and accurate
");
    }

    /**
     * =========================
     * RERANKING
     * =========================
     */
    protected function reRankDocuments(string $query, array $documents): array
    {
        if (count($documents) <= 1) {
            return $documents;
        }

        $prompt = $this->composeReRankPrompt($query, $documents);

        try {
            $response = $this->generator->generate($prompt, [
                'query' => $query,
                'documents' => $documents,
            ]);

            $order = $this->parseReRankResponse($response, count($documents));

            if (!empty($order)) {
                return array_map(fn($i) => $documents[$i], $order);
            }
        } catch (\Throwable $e) {
            // fallback silently
        }

        return $documents;
    }

    protected function composeReRankPrompt(string $query, array $documents): string
    {
        $chunks = collect($documents)
            ->map(
                fn($d, $i) =>
                $i . ") " . Str::limit($d['content'] ?? '', 800)
            )
            ->join("\n\n");

        return "
Rank these documents by relevance to the query.

Query:
{$query}

Documents:
{$chunks}

Return ONLY JSON array like:
[0,1,2]
";
    }

    protected function parseReRankResponse(string $response, int $count): array
    {
        $decoded = json_decode($response, true);

        if (is_array($decoded)) {
            return array_values(array_filter($decoded, fn($i) => $i < $count));
        }

        preg_match_all('/\d+/', $response, $matches);

        $order = array_map('intval', $matches[0] ?? []);

        return array_values(array_unique(
            array_filter($order, fn($i) => $i < $count)
        ));
    }
}
