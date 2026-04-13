<?php

namespace App\Services\Contracts;

interface RetrievalProviderInterface
{
    public function search(string $query, int $limit = 5): array;
}
