<?php

namespace App\Services\Providers;

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

        $ids = array_column($matches, 'document_id');

        return $this->documents->findByIds($ids);
    }
}
