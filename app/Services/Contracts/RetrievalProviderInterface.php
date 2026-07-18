<?php

namespace App\Services\Contracts;

use App\DTO\RetrievalResult;

interface RetrievalProviderInterface
{
    /**
     * @return array<int, RetrievalResult>
     */
    public function search(string $query, int $limit = 5, ?callable $progressCallback = null, ?string $sessionId = null): array;
}
