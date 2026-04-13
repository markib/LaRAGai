<?php

namespace App\Jobs;

use App\Services\RagService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $source;
    public string $content;
    public array $metadata;

    public function __construct(string $source, string $content, array $metadata = [])
    {
        $this->source = $source;
        $this->content = $content;
        $this->metadata = $metadata;
    }

    public function handle(RagService $ragService): void
    {
        $ragService->ingestDocument($this->source, $this->content, $this->metadata);
    }
}
