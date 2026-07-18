<?php

namespace App\Services;

use App\DTO\IngestResult;
use App\DTO\RetrievalResult;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\DocumentEmbedding;
use App\Repositories\QdrantVectorRepository;
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
        protected QdrantVectorRepository $vectors,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | INGESTION PIPELINE
    |--------------------------------------------------------------------------
    */

    public function ingestDocument(int $documentId, string $content): IngestResult
    {
        logger()->info('RAG INGEST START', [
            'document_id' => $documentId,
            'content_length' => strlen($content),
        ]);

        /** @var Document $document */
        $document = Document::query()->findOrFail($documentId);

        // Prevent duplicate ingestion in concurrent jobs
        // if ($document->status === 'processing') {
        //     return [
        //         'document_id' => $document->id,
        //         'skipped' => true,
        //         'reason' => 'already_processing',
        //     ];
        // }

        $document->markAsProcessing();

        try {
            $chunks = $this->chunkContent($content);
            logger()->info('RAG CHUNKS GENERATED', [
                'count' => count($chunks),
            ]);
            foreach ($chunks as $index => $chunkText) {

                logger()->info('CREATING CHUNK', [
                    'index' => $index,
                ]);
                // 1. Create chunk
                /** @var DocumentChunk $chunk */
                $chunk = DocumentChunk::query()->create([
                    'document_id' => $document->id,
                    'chunk_index' => $index,
                    'content' => $chunkText,
                    'token_count' => Str::length($chunkText),
                ]);

                logger()->info('CHUNK CREATED', [
                    'id' => $chunk->id,
                ]);

                // 2. Generate embedding
                $embedding = $this->embedder->embed($chunkText);
                logger()->info('EMBEDDING GENERATED');

                /** @var int $documentId */
                $documentId = $document->id;

                // 3. Store embedding (DB optional)
                DocumentEmbedding::query()->create([
                    'document_id' => $documentId,
                    'chunk_id' => $chunk->id,
                    'model' => config('ollama-laravel.embedding_model', 'nomic-embed-text:latest'),
                ]);

                // 4. Push to vector DB
                $this->vectors->saveEmbedding(
                    $document->id,
                    $embedding,
                    [
                        'document_id' => $document->id,
                        'chunk_id' => $chunk->id,
                        'chunk_index' => $index,
                        'content' => $chunkText,
                    ]
                );
                logger()->info('EMBEDDING SAVED');
            }

            $document->markAsIndexed();

            return new IngestResult(
                documentId: $document->id,
                chunks: count($chunks),
                status: 'indexed'
            );
        } catch (\Throwable $e) {

            $document->markAsFailed($e->getMessage());

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RETRIEVAL
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<int, RetrievalResult>
     */
    public function retrieve(string $query, int $limit = 5, ?callable $progressCallback = null, ?string $sessionId = null): array
    {
        return $this->retriever->search($query, $limit, $progressCallback, $sessionId);
    }

    /*
    |--------------------------------------------------------------------------
    | RAG ANSWER PIPELINE
    |--------------------------------------------------------------------------
     */

    /**
     * @param callable(string,int):void|null $progressCallback
     * @return array{
     *     answer:string,
     *     documents:array<int, RetrievalResult>,
     *     session_id:string|null
     * }
     */
    public function answer(string $query, ?string $sessionId = null, int $limit = 5, ?callable $progressCallback = null): array
    {
        $results = $this->retrieve(
            $query,
            $limit,
            function (string $label, int $percent) use ($sessionId, $progressCallback): void {

                // logger()->info('RAG RETRIEVAL PROGRESS', [
                //     'session_id' => $sessionId,
                //     'label' => $label,
                //     'percent' => $percent,
                // ]);

                if ($progressCallback !== null) {
                    $progressCallback($label, $percent);
                }
            }
        );

        if (empty($results)) {
            return [
                'answer' => "I couldn't find relevant information in your documents.",
                'documents' => [],
                'session_id' => $sessionId,
            ];
        }

        $context = $this->buildContext($results);

        $prompt = $this->buildPrompt($query, $context, $sessionId);

        $answer = $this->generator->generate($prompt, [
            'query' => $query,
            'context' => $context,
        ]);

        return [
            'answer' => $answer,
            'documents' => $results,
            'session_id' => $sessionId,
            'progressCallback' => $progressCallback,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PROMPT BUILDER
    |--------------------------------------------------------------------------
    */

    protected function buildPrompt(string $query, string $context, ?string $sessionId): string
    {
        $system = 'You are a helpful AI assistant. Use ONLY provided context.';

        return <<<PROMPT
{$system}

Context:
{$context}

User Question:
{$query}

Rules:
- Use only provided context
- If unsure, say you don't know
- Be concise
PROMPT;
    }

    /**
     * @param array<int, RetrievalResult> $documents
     */
    protected function buildContext(array $documents): string
    {
        return collect($documents)
            ->map(
                fn (RetrievalResult $doc, int $i) => "[{$i}] {$doc->content}"
            )
            ->implode("\n\n");
    }

    /*
    |--------------------------------------------------------------------------
    | CHUNKING (CLEAN + STABLE)
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<int,string>
     */
    protected function chunkContent(string $content, int $size = 900, int $overlap = 150): array
    {

        $content = preg_replace('/\s+/', ' ', trim($content));

        if (! $content) {
            return [];
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $content);

        if ($sentences === false) {
            return [];
        }

        $chunks = [];
        $chunk = '';

        foreach ($sentences as $sentence) {

            if (strlen($chunk.' '.$sentence) > $size) {
                $chunks[] = trim($chunk);
                $chunk = substr($chunk, -$overlap).' '.$sentence;
            } else {
                $chunk .= ' '.$sentence;
            }
        }

        if ($chunk) {
            $chunks[] = trim($chunk);
        }

        return $chunks;
    }
}
