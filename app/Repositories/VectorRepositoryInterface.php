<?php

namespace App\Repositories;

interface VectorRepositoryInterface
{
    /**
     * Save an embedding vector and its metadata payload.
     *
     * @param  array<int, float>    $embedding
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function saveEmbedding(
        int $documentId,
        array $embedding,
        array $payload = []
    ): array;

    /**
     * Search the vector database for nearest neighbors.
     *
     * @param  array<int, float>                                                                     $embedding
     * @return array<int, array{chunk_id: int|string, document_id?: int|string|null, score?: float}>
     */
    public function search(
        array $embedding,
        int $limit = 5
    ): array;

    public function deletePoint(
        int $pointId
    ): bool;

    public function clearCollection(): bool;
}
