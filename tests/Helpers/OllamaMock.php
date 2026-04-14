<?php

use Cloudstudio\Ollama\Facades\Ollama;
use Illuminate\Support\Facades\Http;

if (! function_exists('generateRandomVector')) {
    function generateRandomVector(int $length = 768): array
    {
        return array_map(fn () => mt_rand(-10000, 10000) / 10000, range(1, $length));
    }
}

if (! function_exists('mockOllamaEmbeddings')) {
    function mockOllamaEmbeddings(?array $vector = null): array
    {
        $vector = $vector ?? generateRandomVector(768);

        Http::fake([
            '*v1/embeddings' => Http::response(['embeddings' => [$vector]], 200),
        ]);

        return $vector;
    }
}

if (! function_exists('mockOllamaCompletion')) {
    function mockOllamaCompletion(string $output = 'This is a mocked Ollama response.'): void
    {
        Ollama::shouldReceive('model')->andReturnSelf();
        Ollama::shouldReceive('prompt')->andReturnSelf();
        Ollama::shouldReceive('ask')->andReturn(['output' => $output]);
    }
}
