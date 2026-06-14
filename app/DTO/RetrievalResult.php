<?php

namespace App\DTO;

final readonly class RetrievalResult
{
    public function __construct(
        public int $id,
        public int $documentId,
        public int $chunkId,
        public int $chunkIndex,
        public string $content,
        public float $score,
        public ?string $filename = null,
        public ?string $originalFilename = null,
        public ?string $source = null,
    ) {}
}
