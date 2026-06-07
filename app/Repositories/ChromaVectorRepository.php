<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ChromaVectorRepository implements VectorRepositoryInterface
{
    protected string $host;
    protected string $apiKey;
    protected string $tenant;
    protected string $database;
    protected string $collection;

    public function __construct()
    {
        $chromaHost = config('rag.chroma.host', 'http://localhost:8000');
        
        // Ensure protocol is set for cloud services
        if (!str_starts_with($chromaHost, 'http://') && !str_starts_with($chromaHost, 'https://')) {
            $chromaHost = 'https://' . $chromaHost;
        }
        
        $this->host = rtrim($chromaHost, '/');
        $this->apiKey = config('rag.chroma.api_key', '');
        $this->tenant = config('rag.chroma.tenant', 'default');
        $this->database = config('rag.chroma.database', 'default');
        $this->collection = config('rag.chroma.collection', 'documents');
    }

    protected function client()
    {
        return Http::acceptJson()
            ->timeout(120)  // Increased from 30s to 120s for remote Chroma service
            ->withHeaders($this->headers());
    }

    protected function headers(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if (!empty($this->apiKey)) {
            // Chroma cloud uses X-API-Key header for authentication
            $headers['X-API-Key'] = $this->apiKey;
        }

        return $headers;
    }

    public function saveEmbedding(int $documentId, array $vector, array $metadata = []): array
    {
        if (empty($vector)) {
            throw new RuntimeException('Invalid embedding vector: vector is empty.');
        }

        $this->ensureCollectionExists();

        // Chroma expects: ids, embeddings, metadatas, documents (optional)
        $payload = [
            'ids' => [(string)$documentId],
            'embeddings' => [$vector],
            'metadatas' => [array_merge([
                'document_id' => $documentId,
            ], $metadata)],
        ];

        // Try new endpoint format with tenant and database in URL
        $url = "{$this->host}/api/v1/tenants/{$this->tenant}/databases/{$this->database}/collections/{$this->collection}/add";

        $response = $this->client()->post($url, $payload);

        if (!$response->successful()) {
            throw new RuntimeException(sprintf(
                'Chroma failed to save vector (%s): %s',
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

        if (empty($vector)) {
            throw new RuntimeException('Invalid embedding vector: vector is empty.');
        }

        $payload = [
            'query_embeddings' => [$vector],
            'n_results' => $limit,
            'include' => ['distances', 'metadatas'],
        ];

        $url = "{$this->host}/api/v1/tenants/{$this->tenant}/databases/{$this->database}/collections/{$this->collection}/query";

        $response = $this->client()->post($url, $payload);

        if (!$response->successful()) {
            throw new RuntimeException('Chroma search failed: ' . $response->body());
        }

        $data = $response->json();

        // Chroma returns results grouped by query
        // Structure: { ids: [[id1, id2, ...]], distances: [[dist1, dist2, ...]], metadatas: [[meta1, meta2, ...]] }
        $ids = $data['ids'][0] ?? [];
        $distances = $data['distances'][0] ?? [];

        $results = [];
        foreach ($ids as $index => $id) {
            $results[] = [
                'document_id' => (int)$id,
                'score' => isset($distances[$index]) ? 1.0 - (float)$distances[$index] : 0.0, // Convert distance to similarity score
            ];
        }

        return $results;
    }

    public function getPoint(int $documentId): ?array
    {
        $url = "{$this->host}/api/v1/tenants/{$this->tenant}/databases/{$this->database}/collections/{$this->collection}/get";
        
        $response = $this->client()
            ->post($url, [
                'ids' => [(string)$documentId],
                'include' => ['embeddings', 'metadatas'],
            ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        if (empty($data['ids']) || count($data['ids']) === 0) {
            return null;
        }

        return [
            'id' => $data['ids'][0],
            'embedding' => $data['embeddings'][0] ?? null,
            'metadata' => $data['metadatas'][0] ?? null,
        ];
    }

    public function deletePoint(int $documentId): bool
    {
        $url = "{$this->host}/api/v1/tenants/{$this->tenant}/databases/{$this->database}/collections/{$this->collection}/delete";
        
        $response = $this->client()
            ->post($url, [
                'ids' => [(string)$documentId],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Chroma delete failed: ' . $response->body());
        }

        return true;
    }

    public function clearCollection(): bool
    {
        // Delete entire collection and recreate it
        $url = "{$this->host}/api/v1/tenants/{$this->tenant}/databases/{$this->database}/collections/{$this->collection}";
        
        $deleteResponse = $this->client()
            ->delete($url);

        if (!$deleteResponse->successful() && $deleteResponse->status() !== 404) {
            throw new RuntimeException('Chroma clear failed: ' . $deleteResponse->body());
        }

        // Recreate the collection
        $this->ensureCollectionExists();

        return true;
    }

    protected function ensureCollectionExists(): void
    {
        // Check if collection exists
        $url = "{$this->host}/api/v1/tenants/{$this->tenant}/databases/{$this->database}/collections/{$this->collection}";
        
        $response = $this->client()
            ->get($url);

        if ($response->status() === 200) {
            return; // Collection exists
        }

        // Create collection if it doesn't exist
        $createUrl = "{$this->host}/api/v1/tenants/{$this->tenant}/databases/{$this->database}/collections";
        
        $createResponse = $this->client()
            ->post($createUrl, [
                'name' => $this->collection,
                'metadata' => [
                    'hnsw:space' => 'cosine', // or 'l2', 'ip' depending on your needs
                ],
            ]);

        if (!$createResponse->successful()) {
            throw new RuntimeException('Failed to create Chroma collection: ' . $createResponse->body());
        }
    }
}
