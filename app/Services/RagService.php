<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\DocumentEmbedding;
use App\Repositories\QdrantVectorRepository;
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
        protected QdrantVectorRepository $vectors,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | INGESTION PIPELINE
    |--------------------------------------------------------------------------
    */

    public function ingestDocument(int $documentId, string $content): array
    {
        logger()->info('RAG INGEST START', [
            'document_id' => $documentId,
            'content_length' => strlen($content),
        ]);

        $document = Document::findOrFail($documentId);

        // Prevent duplicate ingestion in concurrent jobs
        // if ($document->status === 'processing') {
        //     return [
        //         'document_id' => $document->id,
        //         'skipped' => true,
        //         'reason' => 'already_processing',
        //     ];
        // }

        // $document->markAsProcessing();

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
                $chunk = DocumentChunk::create([
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
                logger()->info('Embedding Model', [
                    'model' => config('ollama-laravel.embedding_model'),
                ]);
                // 3. Store embedding (DB optional)
                DocumentEmbedding::create([
                    'document_id' => $document->id,
                    'chunk_id' => $chunk->id,
                    'embedding' => $embedding,
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
                        'content' => Str::limit($chunkText, 200),
                    ]
                );
                logger()->info('EMBEDDING SAVED');
            }
            

            // $document->markAsIndexed();

            return [
                'document_id' => $document->id,
                'chunks' => count($chunks),
                'status' => 'indexed',
            ];
        } catch (\Throwable $e) {

            // $document->markAsFailed($e->getMessage());

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RETRIEVAL
    |--------------------------------------------------------------------------
    */

    public function retrieve(string $query, int $limit = 5): array
    {
        return $this->retriever->search($query, $limit);
    }

    /*
    |--------------------------------------------------------------------------
    | RAG ANSWER PIPELINE
    |--------------------------------------------------------------------------
    */

    public function answer(string $query, ?string $sessionId = null, int $limit = 5): array
    {
        $results = $this->retrieve($query, $limit);

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
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PROMPT BUILDER
    |--------------------------------------------------------------------------
    */

    protected function buildPrompt(string $query, string $context, ?string $sessionId): string
    {
        $system = "You are a helpful AI assistant. Use ONLY provided context.";

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

    protected function buildContext(array $documents): string
    {
        return collect($documents)
            ->map(function ($doc, $i) {
                return "[{$i}] {$doc['content']}";
            })
            ->join("\n\n");
    }

    /*
    |--------------------------------------------------------------------------
    | CHUNKING (CLEAN + STABLE)
    |--------------------------------------------------------------------------
    */

    protected function chunkContent(string $content, int $size = 900, int $overlap = 150): array
    {

        $content = preg_replace('/\s+/', ' ', trim($content));

        if (!$content) {
            return [];
        }

        $chunks = [];
        $start = 0;
        $length = mb_strlen($content);

        while ($start < $length) {
            $chunks[] = mb_substr($content, $start, $size);
            $start += ($size - $overlap);

            logger()->info('INSERTING CHUNK', [
                'index' => $start,
            ]);
        }
        logger()->info('RAG CHUNKS GENERATED', [
            'count' => count($chunks ?? []),
        ]);
        return $chunks;
    }
}
