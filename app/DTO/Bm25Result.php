<?php

namespace App\DTO;

final class Bm25Result
{
    public function __construct(
        public readonly int $id,
        public readonly int $documentId,
        public readonly string $content,
        public readonly float $score,
    ) {}
}
