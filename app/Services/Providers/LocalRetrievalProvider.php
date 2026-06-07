<?php

namespace App\Services\Providers;

use App\Models\DocumentChunk;
use App\Repositories\DocumentRepository;
use App\Repositories\VectorRepositoryInterface;
use App\Services\Contracts\EmbeddingProviderInterface;
use App\Services\Contracts\RetrievalProviderInterface;

class LocalRetrievalProvider implements RetrievalProviderInterface
{
    public function __construct(
        protected EmbeddingProviderInterface $embedder,
        protected VectorRepositoryInterface $vectors,
        protected DocumentRepository $documents
    ) {
    }

    public function search(string $query, int $limit = 5): array
    {
        $queryEmbedding = $this->embedder->embed($query);
        $matches = $this->vectors->search($queryEmbedding, $limit);

        if (empty($matches)) {
            return [];
        }

        $minScore = config('rag.retrieval.min_score', 0.0);
        $matches = array_filter($matches, fn ($match) => isset($match['score']) && (float) $match['score'] >= $minScore);

        if (empty($matches)) {
            return [];
        }

        $chunkIds = array_unique(array_values(array_filter(array_column($matches, 'chunk_id'))));

        if (! empty($chunkIds)) {
            $chunks = DocumentChunk::whereIn('id', $chunkIds)
                ->get()
                ->keyBy('id');

            $results = [];

            foreach ($matches as $match) {
                if (empty($match['chunk_id']) || ! $chunks->has($match['chunk_id'])) {
                    continue;
                }

                $chunk = $chunks->get($match['chunk_id']);

                $results[] = [
                    'id' => $chunk->id,
                    'document_id' => $chunk->document_id,
                    'chunk_id' => $chunk->id,
                    'chunk_index' => $match['chunk_index'] ?? $chunk->metadata['chunk_index'] ?? null,
                    'source' => $match['source'] ?? $chunk->metadata['source'] ?? null,
                    'content' => $chunk->content,
                    'score' => (float) ($match['score'] ?? 0.0),
                    'metadata' => $chunk->metadata,
                ];
            }

            if (! empty($results)) {
                return $results;
            }
        }

        $documentIds = array_unique(array_values(array_filter(array_column($matches, 'document_id'))));
        $documents = $this->documents->findByIds($documentIds);

        return collect($documents)
            ->map(function ($document, $index) use ($matches) {
                $match = $matches[$index] ?? [];
                $document['score'] = isset($match['score']) ? (float) $match['score'] : 0.0;

                return $document;
            })
            ->toArray();
    }
}
