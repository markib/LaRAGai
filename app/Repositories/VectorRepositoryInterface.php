<?php

namespace App\Repositories;

interface VectorRepositoryInterface
{
    public function saveEmbedding(int $documentId, array $vector, array $metadata = []): array;

    public function search(array $vector, int $limit = 5): array;

    public function getPoint(int $documentId): ?array;

    public function deletePoint(int $documentId): bool;

    public function clearCollection(): bool;
}
