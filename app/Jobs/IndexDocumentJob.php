<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentParser;
use App\Services\RagService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexDocumentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $documentId
    ) {}

    public function handle(
        RagService $ragService,
        DocumentParser $parser
    ): void {
        $document = Document::query()->findOrFail($this->documentId);

        // Prevent duplicate processing
        if ($document->status === 'processing') {
            return;
        }

        $document->update([
            'status' => 'processing',
        ]);

        try {
            // Parse file → raw text only
            $content = $parser->parse(
                $document->disk,
                $document->path,
                $document->mime_type
            );

            // Delegate ALL RAG logic to service
            $ragService->ingestDocument(
                documentId: $document->id,
                content: $content
            );

            $document->update([
                'status' => 'indexed',
                'indexed_at' => now(),
            ]);
        } catch (\Throwable $e) {

            $document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
