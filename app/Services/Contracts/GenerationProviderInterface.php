<?php

namespace App\Services\Contracts;

interface GenerationProviderInterface
{
    public function generate(string $prompt, array $context = []): string;
}
