<?php

namespace App\Services\Providers;

use App\Services\Contracts\EmbeddingProviderInterface;
use App\Services\Contracts\GenerationProviderInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIProvider implements EmbeddingProviderInterface, GenerationProviderInterface
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $embeddingModel;
    protected string $generationModel;

    public function __construct()
    {
        $this->apiKey = config('rag.openai.api_key');
        $this->baseUrl = rtrim(config('rag.openai.base_url'), '/');
        $this->embeddingModel = config('rag.openai.embedding_model');
        $this->generationModel = config('rag.openai.generation_model');

        if (empty($this->apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY is required for the OpenAI provider.');
        }
    }

    public function embed(string $text): array
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/embeddings", [
                'model' => $this->embeddingModel,
                'input' => $text,
            ]);

        $payload = $response->json();

        if (!isset($payload['data'][0]['embedding'])) {
            throw new RuntimeException('Failed to create embeddings from OpenAI.');
        }

        return $payload['data'][0]['embedding'];
    }

    public function generate(string $prompt, array $context = []): string
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->generationModel,
                'messages' => [
                    ['role' => 'system', 'content' => config('rag.prompt.system')],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
                'max_tokens' => 700,
            ]);

        $payload = $response->json();

        return $payload['choices'][0]['message']['content'] ?? throw new RuntimeException('OpenAI generation failed.');
    }
}
