<?php

namespace App\DTO;

final class VectorResult
{
    public function __construct(
        public readonly int $chunkId,
        public readonly int $documentId,
        public readonly string $content,
        public readonly float $score,
    ) {}
}
