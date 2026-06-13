<?php

namespace App\Services\Providers;

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

    public function search(string $query, int $limit = 5): array
    {
        $queryEmbedding = $this->embedder->embed($query);

        // Vector search
        $vectorResults = $this->vectors->search(
            $queryEmbedding,
            $limit * 4
        );

        // BM25 search
        $bm25Results = $this->bm25Retriever->search(
            $query,
            $limit * 4
        );

        // Hybrid merge
        $matches = $this->reciprocalRankFusion(
            $vectorResults,
            $bm25Results,
            $limit
        );

        if (empty($matches)) {
            return [];
        }

        return $this->hydrateChunks($matches);
    }

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

            $chunkId = $result['id'];

            $scores[$chunkId] ??= [
                'chunk_id' => $chunkId,
                'document_id' => $result['document_id'],
                'score' => 0,
            ];

            $scores[$chunkId]['score']
                += 1 / (60 + $rank + 1);
        }

        uasort(
            $scores,
            fn($a, $b) => $b['score'] <=> $a['score']
        );

        return array_slice(
            array_values($scores),
            0,
            $limit
        );
    }

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

            $results[] = [
                'id' => $chunk->id,
                'document_id' => $chunk->document_id,
                'chunk_id' => $chunk->id,
                'chunk_index' => $chunk->chunk_index,
                'content' => $chunk->content,
                'filename' => $chunk->document?->filename,
                'original_filename' => $chunk->document?->original_filename,
                'score' => $match['score'],
            ];
        }

        return $results;
    }
}
