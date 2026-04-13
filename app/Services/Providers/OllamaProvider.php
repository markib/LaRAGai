<?php

namespace App\Services\Providers;

use App\Services\Contracts\EmbeddingProviderInterface;
use App\Services\Contracts\GenerationProviderInterface;
use Cloudstudio\Ollama\Facades\Ollama;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaProvider implements EmbeddingProviderInterface, GenerationProviderInterface
{
    protected string $model;
    protected string $embeddingModel;
    protected array $embeddingModelCandidates;

    public function __construct()
    {
        $this->model = config('ollama.model', config('ollama-laravel.model', 'phi4-mini:latest'));
        $this->embeddingModel = config('ollama.embedding_model', config('ollama-laravel.embedding_model', 'nomic-embed-text'));
        $this->embeddingModelCandidates = array_values(array_unique(array_filter([
            $this->embeddingModel,
            $this->embeddingModel . ':latest',
            str_replace('-', '/', $this->embeddingModel),
            str_replace('-', '/', $this->embeddingModel) . ':latest',
        ])));
    }

    public function embed(string $text): array
    {
        $errors = [];

        foreach ($this->embeddingModelCandidates as $candidate) {
            try {
                $embedding = $this->requestEmbedding($candidate, $text);

                if (is_array($embedding) && count($embedding) > 0) {
                    return $embedding;
                }

                $errors[] = sprintf('%s returned empty embedding', $candidate);
            } catch (RuntimeException $e) {
                $errors[] = sprintf('%s error: %s', $candidate, $e->getMessage());
            }
        }

        throw new RuntimeException(
            'Ollama failed to create embeddings for any candidate model: ' . implode(' | ', $errors)
        );
    }

    protected function requestEmbedding(string $model, string $text): array
    {
        $url = rtrim(config('ollama-laravel.url', 'http://127.0.0.1:11434'), '/') . '/v1/embeddings';
        $response = Http::timeout(config('ollama-laravel.connection.timeout', 300))
            ->post($url, [
                'model' => $model,
                'input' => $text,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Ollama request failed for ' . $model . ': ' . $response->body());
        }

        $payload = $response->json();

        if (isset($payload['error'])) {
            throw new RuntimeException('Ollama error for ' . $model . ': ' . $payload['error']);
        }

        $embedding = null;

        if (isset($payload['embedding']) && is_array($payload['embedding'])) {
            $embedding = $payload['embedding'];
        }

        if ($embedding === null && isset($payload['data']) && is_array($payload['data']) && count($payload['data']) > 0) {
            $first = reset($payload['data']);
            if (is_array($first)) {
                if (isset($first['embedding']) && is_array($first['embedding'])) {
                    $embedding = $first['embedding'];
                } elseif (isset($first['embeddings']) && is_array($first['embeddings'])) {
                    $embedding = count($first['embeddings']) > 0 && is_array($first['embeddings'][0])
                        ? $first['embeddings'][0]
                        : $first['embeddings'];
                }
            }
        }

        if ($embedding === null && isset($payload['embeddings']) && is_array($payload['embeddings'])) {
            $embedding = count($payload['embeddings']) > 0 && is_array($payload['embeddings'][0])
                ? $payload['embeddings'][0]
                : $payload['embeddings'];
        }

        if (! is_array($embedding) || count($embedding) === 0) {
            throw new RuntimeException(
                'Ollama returned no embedding for ' . $model . ': ' . json_encode($payload)
            );
        }

        return $embedding;
    }

    public function generate(string $prompt, array $context = []): string
    {
        $result = Ollama::model($this->model)
            ->prompt($prompt)
            ->ask();

        if (isset($result['error'])) {
            throw new RuntimeException('Ollama generation failed: ' . $result['error']);
        }

        $output = $result['output'] ?? null;

        if (is_array($output)) {
            return implode('', array_map('strval', $output));
        }

        if (is_string($output)) {
            return $output;
        }

        if (isset($result['response']) && is_string($result['response'])) {
            return $result['response'];
        }

        throw new RuntimeException('Ollama generation failed: unexpected response.');
    }
}
