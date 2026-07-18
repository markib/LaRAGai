<?php

namespace App\Services\Contracts;

interface GenerationProviderInterface
{
    /**
     * Generate a response based on a prompt and provided context.
     *
     * @param array<string, mixed> $context
     */
    public function generate(string $prompt, array $context = []): string;
}
