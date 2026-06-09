<?php

namespace App\Repositories;

interface VectorRepositoryInterface
{
    public function saveEmbedding(
        int $documentId,
        array $embedding,
        array $payload = []
    ): array;

    public function search(
        array $embedding,
        int $limit = 5
    ): array;

    public function deletePoint(
        int $pointId
    ): bool;

    public function clearCollection(): bool;
}
