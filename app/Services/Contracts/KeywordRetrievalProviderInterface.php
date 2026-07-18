<?php

namespace App\Services\Contracts;

use App\DTO\RetrievalResult;

interface KeywordRetrievalProviderInterface
{
    /**
     * @return array<int, RetrievalResult>
     */
    public function search(
        string $query,
        int $limit = 10
    ): array;
}
