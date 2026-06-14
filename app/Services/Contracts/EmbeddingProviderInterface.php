<?php

namespace App\Services\Contracts;

interface EmbeddingProviderInterface
{
    /**
     * @return array<int,float>
     */
    public function embed(string $text): array;
}
