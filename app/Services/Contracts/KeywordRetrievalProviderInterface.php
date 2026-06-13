<?php

namespace App\Services\Contracts;

interface KeywordRetrievalProviderInterface
{
    public function search(
        string $query,
        int $limit = 10
    ): array;
}
