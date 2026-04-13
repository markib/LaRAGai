<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class QdrantVectorRepository implements VectorRepositoryInterface
{
    private const VECTOR_NAME = 'embeddings';

    protected string $host;
    protected ?string $apiKey;
    protected string $collection;
    protected int $dimension;
    protected string $distance;
    protected string $vectorName;

    public function __construct()
    {
        $this->host = rtrim(config('rag.qdrant.host', 'http://127.0.0.1:6333'), '/');
        $this->apiKey = config('rag.qdrant.api_key');
        $this->collection = config('rag.qdrant.collection', 'documents');
        $this->dimension = config('rag.qdrant.vector_dim', 1536);
        $this->distance = config('rag.qdrant.distance', 'Cosine');
        $this->vectorName = config('rag.qdrant.vector_name', self::VECTOR_NAME);
    }

    protected function client()
    {
        return Http::acceptJson()
            ->timeout(30)
            ->withHeaders($this->headers());
    }

    protected function headers(): array
    {
        $headers = ['Accept' => 'application/json'];

        if (! empty($this->apiKey)) {
            $headers['X-API-Key'] = $this->apiKey;
            $headers['Authorization'] = "Bearer {$this->apiKey}";
        }

        return $headers;
    }

    protected function resolveCollectionVectors(array $body): array
    {
        $candidateVectors = [
            $body['result']['vectors'] ?? null,
            $body['vectors'] ?? null,
            $body['result']['config']['params']['vectors'] ?? null,
            $body['result']['config']['vectors'] ?? null,
            $body['config']['params']['vectors'] ?? null,
            $body['config']['vectors'] ?? null,
        ];

        foreach ($candidateVectors as $vectors) {
            if (is_array($vectors)) {
                return $this->normalizeVectorDefinitions($vectors);
            }
        }

        return [];
    }

    protected function normalizeVectorDefinitions(array $vectors): array
    {
        if ($this->isAssociativeArray($vectors)) {
            return $vectors;
        }

        $normalized = [];

        foreach ($vectors as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            if (isset($definition['name'])) {
                $normalized[$definition['name']] = $definition;
                continue;
            }

            if (isset($definition['vector_name'])) {
                $normalized[$definition['vector_name']] = $definition;
                continue;
            }
        }

        return $normalized;
    }

    protected function isAssociativeArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    protected function upsertPoints(array $payload)
    {
        $url = "{$this->host}/collections/{$this->collection}/points?wait=true";

        $response = $this->client()->put($url, $payload);

        if (! $response->successful()) {
            $response = $this->client()->post($url, $payload);
        }

        return $response;
    }

    public function saveEmbedding(int $documentId, array $vector, array $metadata = []): array
    {
        if (empty($vector)) {
            throw new \RuntimeException('Invalid embedding vector: vector is empty.');
        }

        $vectorLength = count($vector);

        if ($vectorLength !== $this->dimension) {
            $this->handleDimensionMismatch($vectorLength);
        }

        $this->ensureCollectionExists();

        $payload = [
            'points' => [
                [
                    'id' => $documentId,
                    'vectors' => [
                        $this->vectorName => $vector,
                    ],
                    'payload' => array_merge([
                        'document_id' => $documentId,
                    ], $metadata),
                ],
            ],
        ];

        $response = $this->upsertPoints($payload);

        if (! $response->successful()) {
            $body = $response->body();

            if (str_contains($body, 'Wrong input: Not existing vector name error') || str_contains($body, 'Not existing vector name error')) {
                $this->ensureCollectionExists();

                $payload = [
                    'points' => [
                        [
                            'id' => $documentId,
                            'vectors' => [
                                $this->vectorName => $vector,
                            ],
                            'payload' => array_merge([
                                'document_id' => $documentId,
                            ], $metadata),
                        ],
                    ],
                ];

                $response = $this->upsertPoints($payload);

                if (! $response->successful()) {
                    $legacyPayload = [
                        'points' => [
                            [
                                'id' => $documentId,
                                'vector' => $vector,
                                'payload' => array_merge([
                                    'document_id' => $documentId,
                                ], $metadata),
                            ],
                        ],
                    ];

                    $response = $this->upsertPoints($legacyPayload);

                    if ($response->successful()) {
                        $this->vectorName = 'vector';
                    }
                }
            }

            if (! $response->successful() && (str_contains($body, 'Format error in JSON body') || str_contains($body, 'missing field `ids`') || str_contains($body, 'missing field ids'))) {
                $alternatePayload = [
                    'ids' => [$documentId],
                    'vectors' => $this->vectorName !== 'vector'
                        ? [$this->vectorName => [$vector]]
                        : [$vector],
                    'payloads' => [array_merge([
                        'document_id' => $documentId,
                    ], $metadata)],
                ];

                $response = $this->upsertPoints($alternatePayload);
            }
        }

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'Qdrant failed to save vector (%s): %s',
                $response->status(),
                $response->body()
            ));
        }

        return [
            'document_id' => $documentId,
            'metadata' => $metadata,
        ];
    }

    public function search(array $vector, int $limit = 5): array
    {
        $this->ensureCollectionExists();

        if (empty($vector) || count($vector) !== $this->dimension) {
            throw new \RuntimeException(
                sprintf(
                    'Invalid embedding vector. Expected %d dimensions, got: %d',
                    $this->dimension,
                    count($vector)
                )
            );
        }

        $response = $this->searchPoints($vector, $limit, $this->vectorName);

        if (! $response->successful()) {
            throw new \RuntimeException('Qdrant search failed: ' . $response->body());
        }

        $hits = $response->json('result', []);

        return array_map(fn ($hit) => [
            'document_id' => isset($hit['payload']['document_id']) ? (int) $hit['payload']['document_id'] : (int) $hit['id'],
            'score' => isset($hit['score']) ? (float) $hit['score'] : 0.0,
        ], $hits);
    }

    protected function searchPoints(array $vector, int $limit, string $vectorName)
    {
       $payload = [
        'vector' => [
            'name' => $vectorName,   // 🔥 FIX
            'vector' => $vector,
        ],
        'limit' => $limit,
        'with_payload' => true,     // usually needed for RAG
    ];

        return $this->client()
            ->post("{$this->host}/collections/{$this->collection}/points/search", $payload);
    }

    public function getPoint(int $documentId): ?array
    {
        $response = $this->client()
            ->get("{$this->host}/collections/{$this->collection}/points/{$documentId}");

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new RuntimeException('Qdrant fetch failed: ' . $response->body());
        }

        return $response->json();
    }

    public function deletePoint(int $documentId): bool
    {
        $response = $this->client()
            ->delete("{$this->host}/collections/{$this->collection}/points", [
                'points' => [$documentId],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Qdrant delete failed: ' . $response->body());
        }

        return true;
    }

    public function clearCollection(): bool
    {
        $response = $this->client()
            ->post("{$this->host}/collections/{$this->collection}/points/delete", [
                'filter' => [
                    'must' => [],
                ],
                'force' => true,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Qdrant clear failed: ' . $response->body());
        }

        return true;
    }

    protected function ensureCollectionExists(): void
    {
        $response = $this->client()
            ->get("{$this->host}/collections/{$this->collection}");

        if ($response->status() === 200) {
            $body = $response->json();
            $vectors = $this->resolveCollectionVectors($body);
            $vectorSizes = $this->resolveCollectionVectorDimensions($body);
            $this->syncVectorNameWithCollection($vectors);

            if (isset($vectorSizes[$this->vectorName])) {
                $this->dimension = $vectorSizes[$this->vectorName];
            }

            return;
        }

        $response = $this->client()
            ->put("{$this->host}/collections/{$this->collection}", [
                'vectors' => [
                    $this->vectorName => [
                        'size' => $this->dimension,
                        'distance' => $this->distance,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to create Qdrant collection: ' . $response->body());
        }
    }

    protected function handleDimensionMismatch(int $vectorLength): void
    {
        $response = $this->client()
            ->get("{$this->host}/collections/{$this->collection}");

        if ($response->status() !== 200) {
            $this->dimension = $vectorLength;
            return;
        }

        $body = $response->json();
        $vectors = $this->resolveCollectionVectors($body);
        $this->syncVectorNameWithCollection($vectors);
        $vectorSizes = $this->resolveCollectionVectorDimensions($body);
        $currentSize = $vectorSizes[$this->vectorName] ?? null;

        if ($currentSize === null) {
            $this->dimension = $vectorLength;
            return;
        }

        if ($currentSize === $vectorLength) {
            $this->dimension = $vectorLength;
            return;
        }

        $pointCount = $this->resolveCollectionPointCount($body);

        if ($pointCount === 0) {
            $this->deleteCollection();
            $this->dimension = $vectorLength;
            return;
        }

        throw new RuntimeException(sprintf(
            'Qdrant collection "%s" was created with vector dimension %d but the embedding length is %d. ' .
            'Please recreate the collection with the matching dimension or set QDRANT_VECTOR_DIM=%d.',
            $this->collection,
            $currentSize,
            $vectorLength,
            $vectorLength
        ));
    }

    protected function deleteCollection(): void
    {
        $response = $this->client()
            ->delete("{$this->host}/collections/{$this->collection}");

        if (! $response->successful()) {
            throw new RuntimeException('Failed to delete Qdrant collection: ' . $response->body());
        }
    }

    protected function resolveCollectionVectorDimensions(array $body): array
    {
        $vectors = $this->resolveCollectionVectors($body);
        $dimensions = [];

        foreach ($vectors as $name => $definition) {
            if (is_array($definition) && isset($definition['size'])) {
                $dimensions[$name] = (int) $definition['size'];
                continue;
            }

            if (is_numeric($definition)) {
                $dimensions[$name] = (int) $definition;
            }
        }

        return $dimensions;
    }

    protected function resolveCollectionPointCount(array $body): ?int
    {
        if (isset($body['result']['point_count'])) {
            return (int) $body['result']['point_count'];
        }

        if (isset($body['result']['points_count'])) {
            return (int) $body['result']['points_count'];
        }

        if (isset($body['result']['status']['points_count'])) {
            return (int) $body['result']['status']['points_count'];
        }

        if (isset($body['result']['status']['pointsCount'])) {
            return (int) $body['result']['status']['pointsCount'];
        }

        return null;
    }

    protected function syncVectorNameWithCollection(array $vectors): void
    {
        if (isset($vectors[$this->vectorName])) {
            return;
        }

        if (isset($vectors['vector'])) {
            $this->vectorName = 'vector';
            return;
        }

        if (count($vectors) === 1) {
            $this->vectorName = array_key_first($vectors);
            return;
        }

        if (! empty($vectors) && ! isset($vectors[$this->vectorName])) {
            $this->vectorName = array_key_first($vectors);
        }
    }
}
