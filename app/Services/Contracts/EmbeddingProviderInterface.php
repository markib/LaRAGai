<?php

namespace App\Services\Contracts;

interface EmbeddingProviderInterface
{
    public function embed(string $text): array;
}
