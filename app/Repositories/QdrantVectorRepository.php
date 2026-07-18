<?php

namespace App\Repositories;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
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
        $this->host = rtrim((string) config('rag.qdrant.host', 'http://127.0.0.1:6333'), '/');
        $this->apiKey = config('rag.qdrant.api_key') !== null ? (string) config('rag.qdrant.api_key') : null;
        $this->collection = (string) config('rag.qdrant.collection', 'documents');
        $this->dimension = (int) config('rag.qdrant.vector_dim', 1536);
        $this->distance = (string) config('rag.qdrant.distance', 'Cosine');
        $this->vectorName = (string) config('rag.qdrant.vector_name', self::VECTOR_NAME);
    }

    /**
     * Get the configured HTTP client builder instance.
     */
    protected function client(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout(30)
            ->withHeaders($this->headers());
    }

    /**
     * Get the headers required for Qdrant API requests.
     *
     * @return array<string, string>
     */
    protected function headers(): array
    {
        $headers = ['Accept' => 'application/json'];

        if (! empty($this->apiKey)) {
            $headers['X-API-Key'] = $this->apiKey;
            $headers['Authorization'] = "Bearer {$this->apiKey}";
        }

        return $headers;
    }

    /**
     * Resolve vector definitions out of a generic cluster payload.
     *
     * @param  array<string, mixed> $body
     * @return array<string, mixed>
     */
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

    /**
     * Normalize mixed named/unnamed vector definitions returned from engines.
     *
     * @param  array<mixed>         $vectors
     * @return array<string, mixed>
     */
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

            if (isset($definition['name']) && is_string($definition['name'])) {
                $normalized[$definition['name']] = $definition;

                continue;
            }

            if (isset($definition['vector_name']) && is_string($definition['vector_name'])) {
                $normalized[$definition['vector_name']] = $definition;

                continue;
            }
        }

        return $normalized;
    }

    /**
     * Check if the provided array is an associative array.
     *
     * @param array<mixed> $array
     */
    protected function isAssociativeArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Execute a point upsert action against the target cluster.
     *
     * @param array<string, mixed> $payload
     */
    protected function upsertPoints(array $payload): Response
    {
        $url = "{$this->host}/collections/{$this->collection}/points?wait=true";

        $response = $this->client()->put($url, $payload);

        if (! $response->successful()) {
            $response = $this->client()->post($url, $payload);
        }

        return $response;
    }

    /**
     * Save an embedding vector and its metadata payload.
     *
     * @param  array<int, float>    $embedding
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function saveEmbedding(int $documentId, array $embedding, array $payload = []): array
    {
        if (empty($embedding)) {
            throw new RuntimeException('Invalid embedding vector: vector is empty.');
        }

        $vectorLength = count($embedding);

        if ($vectorLength !== $this->dimension) {
            $this->handleDimensionMismatch($vectorLength);
        }

        $this->ensureCollectionExists();

        $pointsPayload = [
            'points' => [
                [
                    'id' => $documentId,
                    'vectors' => [
                        $this->vectorName => $embedding,
                    ],
                    'payload' => array_merge([
                        'document_id' => $documentId,
                    ], $payload),
                ],
            ],
        ];

        $response = $this->upsertPoints($pointsPayload);

        if (! $response->successful()) {
            $body = $response->body();

            if (str_contains($body, 'Wrong input: Not existing vector name error') || str_contains($body, 'Not existing vector name error')) {
                $this->ensureCollectionExists();

                $pointsPayload = [
                    'points' => [
                        [
                            'id' => $documentId,
                            'vectors' => [
                                $this->vectorName => $embedding,
                            ],
                            // FIXED: Removed redundant null coalescing checks tracking deterministic array states
                            'payload' => array_merge([
                                'document_id' => $documentId,
                            ], $pointsPayload['points'][0]['payload']),
                        ],
                    ],
                ];

                $response = $this->upsertPoints($pointsPayload);

                if (! $response->successful()) {
                    $legacyPayload = [
                        'points' => [
                            [
                                'id' => $documentId,
                                'vector' => $embedding,
                                'payload' => array_merge([
                                    'document_id' => $documentId,
                                ], $pointsPayload['points'][0]['payload']),
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
                        ? [$this->vectorName => [$embedding]]
                        : [$embedding],
                    'payloads' => [array_merge([
                        'document_id' => $documentId,
                    ], $pointsPayload['points'][0]['payload'])],
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
            'metadata' => $pointsPayload['points'][0]['payload'],
        ];
    }

    /**
     * Search the vector database for nearest neighbors.
     *
     * @param  array<int, float>                                                                                                                                          $embedding
     * @return array<int, array{chunk_id: int|string, document_id?: int|string|null, score?: float, chunk_index?: mixed, source?: mixed, payload?: array<string, mixed>}>
     */
    public function search(array $embedding, int $limit = 5): array
    {
        $this->ensureCollectionExists();

        if (empty($embedding) || count($embedding) !== $this->dimension) {
            throw new RuntimeException(
                sprintf(
                    'Invalid embedding vector. Expected %d dimensions, got: %d',
                    $this->dimension,
                    count($embedding)
                )
            );
        }

        $scoreThreshold = (float) config('rag.retrieval.min_score', 0.0);
        $response = $this->searchPoints($embedding, $limit, $this->vectorName, $scoreThreshold);

        if (! $response->successful()) {
            throw new RuntimeException('Qdrant search failed: '.$response->body());
        }

        /** @var array<int, array{id: int|string, score?: float, payload?: array<string, mixed>}> $hits */
        $hits = $response->json('result', []);

        return array_map(fn ($hit) => [
            'document_id' => isset($hit['payload']['document_id']) ? (int) $hit['payload']['document_id'] : (int) $hit['id'],
            'chunk_id' => isset($hit['payload']['chunk_id']) ? (int) $hit['payload']['chunk_id'] : (int) $hit['id'],
            'chunk_index' => $hit['payload']['chunk_index'] ?? null,
            'source' => $hit['payload']['source'] ?? null,
            'score' => isset($hit['score']) ? (float) $hit['score'] : 0.0,
            'payload' => $hit['payload'] ?? [],
        ], $hits);
    }

    /**
     * Perform the actual query vector search network transaction.
     *
     * @param array<int, float> $vector
     */
    protected function searchPoints(array $vector, int $limit, string $vectorName, float $scoreThreshold = 0.0): Response
    {
        $payload = [
            'vector' => [
                'name' => $vectorName,
                'vector' => $vector,
            ],
            'limit' => $limit,
            'with_payload' => true,
        ];

        if ($scoreThreshold > 0.0) {
            $payload['score_threshold'] = $scoreThreshold;
        }

        return $this->client()
            ->post("{$this->host}/collections/{$this->collection}/points/search", $payload);
    }

    /**
     * Retrieve a specific point definition by ID.
     *
     * @return array<string, mixed>|null
     */
    public function getPoint(int $documentId): ?array
    {
        $response = $this->client()
            ->get("{$this->host}/collections/{$this->collection}/points/{$documentId}");

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new RuntimeException('Qdrant fetch failed: '.$response->body());
        }

        /** @var array<string, mixed>|null $data */
        $data = $response->json();

        return $data;
    }

    public function deletePoint(int $documentId): bool
    {
        $response = $this->client()
            ->delete("{$this->host}/collections/{$this->collection}/points", [
                'points' => [$documentId],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Qdrant delete failed: '.$response->body());
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
            throw new RuntimeException('Qdrant clear failed: '.$response->body());
        }

        return true;
    }

    protected function ensureCollectionExists(): void
    {
        $response = $this->client()
            ->get("{$this->host}/collections/{$this->collection}");

        if ($response->status() === 200) {
            /** @var array<string, mixed> $body */
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
            throw new RuntimeException('Failed to create Qdrant collection: '.$response->body());
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

        /** @var array<string, mixed> $body */
        $body = $response->json();
        $vectors = $this->resolveCollectionVectors($body);
        $this->syncVectorNameWithCollection($vectors);
        $vectorSizes = $this->resolveCollectionVectorDimensions($body);
        $currentSize = $vectorSizes[$this->vectorName] ?? null;

        if ($currentSize === null || $currentSize === $vectorLength) {
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
            'Qdrant collection "%s" was created with vector dimension %d but the embedding length is %d. '.
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
            throw new RuntimeException('Failed to delete Qdrant collection: '.$response->body());
        }
    }

    /**
     * Resolve mapped collection dimension properties out of dynamic payload configuration options.
     *
     * @param  array<string, mixed> $body
     * @return array<string, int>
     */
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

    /**
     * Resolve vector engine document counting trackers out of cluster body specifications.
     *
     * @param array<string, mixed> $body
     */
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

    /**
     * Sync local context definitions configuration vectors matching parameters.
     *
     * @param array<string, mixed> $vectors
     */
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
            $this->vectorName = (string) array_key_first($vectors);

            return;
        }

        if (! empty($vectors)) {
            $this->vectorName = (string) array_key_first($vectors);
        }
    }
}
