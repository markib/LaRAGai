<?php

namespace App\Services\Providers;

use App\DTO\Bm25Result;
use App\DTO\RetrievalResult;
use App\Events\RetrievalProgressUpdated;
use App\Models\DocumentChunk;
use App\Repositories\DocumentRepository;
use App\Repositories\VectorRepositoryInterface;
use App\Services\Contracts\EmbeddingProviderInterface;
use App\Services\Contracts\RetrievalProviderInterface;
use App\Services\Retrieval\PostgresBm25Retriever;

class LocalRetrievalProvider implements RetrievalProviderInterface
{
    public function __construct(
        protected EmbeddingProviderInterface $embedder,
        protected VectorRepositoryInterface $vectors,
        protected DocumentRepository $documents,
        protected PostgresBm25Retriever $bm25Retriever
    ) {}

    /**
     * @return array<int, RetrievalResult>
     */
    public function search(string $query, int $limit = 5,?callable $progressCallback = null,?string $sessionId = null): array
    {
        // 1. Pass $sessionId as the 4th argument to trigger Reverb broadcasts
        $this->notifyProgress($progressCallback, 'Searching embeddings', 25, $sessionId);
        $queryEmbedding = $this->embedder->embed($query);

        // Vector search
        $this->notifyProgress($progressCallback, 'Searching embeddings', 100, $sessionId); // Complete previous visual block
        $this->notifyProgress($progressCallback, 'Searching BM25', 25, $sessionId); // Move to BM25 tracking

        $vectorResults = $this->vectors->search(
            $queryEmbedding,
            $limit * 4
        );

        // BM25 search
        $this->notifyProgress($progressCallback, 'Searching BM25', 100, $sessionId);
        $this->notifyProgress($progressCallback, 'Hybrid ranking', 25, $sessionId);

        $bm25Results = $this->bm25Retriever->search(
            $query,
            $limit * 4
        );

        // Hybrid merge
        $this->notifyProgress($progressCallback, 'Hybrid ranking', 100, $sessionId);
        $matches = $this->reciprocalRankFusion(
            $vectorResults,
            $bm25Results,
            $limit
        );

        if (empty($matches)) {
            $this->notifyProgress($progressCallback, 'Generating answer', 0, $sessionId);
            return [];
        }

        $result = $this->hydrateChunks($matches);

        // Hand off control to the LLM generation phase
        $this->notifyProgress($progressCallback, 'Generating answer', 20, $sessionId);

        return $result;
    }

    /**
     * @param  array<int, array{chunk_id?: string|int, document_id?: string|int, score?: float|int}>   $vectorResults
     * @param  array<int, Bm25Result>                                                                  $bm25Results   <-- FIXED: Pointing directly to your DTO class
     * @return array<int, array{chunk_id: string|int, document_id: string|int|null, score: float|int}>
     */
    protected function reciprocalRankFusion(
        array $vectorResults,
        array $bm25Results,
        int $limit
    ): array {
        $scores = [];

        foreach ($vectorResults as $rank => $result) {

            $chunkId = $result['chunk_id'] ?? null;

            if (! $chunkId) {
                continue;
            }

            $scores[$chunkId] ??= [
                'chunk_id' => $chunkId,
                'document_id' => $result['document_id'] ?? null,
                'score' => 0,
            ];

            $scores[$chunkId]['score']
                += 1 / (60 + $rank + 1);
        }

        foreach ($bm25Results as $rank => $result) {

            $chunkId = $result->id;

            $scores[$chunkId] ??= [
                'chunk_id' => $chunkId,
                'document_id' => $result->documentId,
                'score' => 0,
            ];

            $scores[$chunkId]['score']
                += 1 / (60 + $rank + 1);
        }

        uasort(
            $scores,
            fn ($a, $b) => $b['score'] <=> $a['score']
        );

        return array_slice(
            array_values($scores),
            0,
            $limit
        );
    }

    /**
     * @param  array<int, array{chunk_id: string|int, document_id?: string|int|null, score: float|int}> $matches
     * @return array<int, RetrievalResult>
     */
    protected function hydrateChunks(array $matches): array
    {
        $chunkIds = array_column(
            $matches,
            'chunk_id'
        );

        $chunks = DocumentChunk::with('document')
            ->whereIn('id', $chunkIds)
            ->get()
            ->keyBy('id');

        $results = [];

        foreach ($matches as $match) {

            $chunkId = $match['chunk_id'];

            if (! $chunks->has($chunkId)) {
                continue;
            }

            $chunk = $chunks[$chunkId];

            $results[] = new RetrievalResult(
                id: $chunk->id,
                documentId: $chunk->document_id,
                chunkId: $chunk->id,
                chunkIndex: $chunk->chunk_index,
                content: $chunk->content,
                score: $match['score'],
                filename: $chunk->document?->filename,
                originalFilename: $chunk->document?->original_filename,
                source: $chunk->document?->source,
            );
        }

        return $results;
    }

    private function notifyProgress(
        ?callable $callback,
        string $label,
        int $percent,
        ?string $sessionId = null,
    ): void {

        if ($callback) {
            $callback($label, $percent);
        }

        if ($sessionId) {
            broadcast(new RetrievalProgressUpdated(
                sessionId: $sessionId,
                label: $label,
                percent: $percent,
            ));
        }
    }
}
