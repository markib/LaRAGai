<?php

namespace App\DTO;

final class IngestResult
{
    public function __construct(
        public readonly int $documentId,
        public readonly int $chunks,
        public readonly string $status,
    ) {}
}
