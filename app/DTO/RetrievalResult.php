<?php

namespace App\DTO;

use Livewire\Wireable;

final readonly class RetrievalResult implements Wireable
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

    public function toLivewire(): array
    {
        return [
            'id' => $this->id,
            'documentId' => $this->documentId,
            'chunkId' => $this->chunkId,
            'chunkIndex' => $this->chunkIndex,
            'content' => $this->content,
            'score' => $this->score,
            'filename' => $this->filename,
            'originalFilename' => $this->originalFilename,
            'source' => $this->source,
        ];
    }

    public static function fromLivewire($value): static
    {
        return new self(
            $value['id'],
            $value['documentId'],
            $value['chunkId'],
            $value['chunkIndex'],
            $value['content'],
            $value['score'],
            $value['filename'],
            $value['originalFilename'],
            $value['source']
        );
    }
}
