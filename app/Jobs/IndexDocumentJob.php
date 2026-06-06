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

        $document = Document::findOrFail(
            $this->documentId
        );

        $document->update([
            'status' => 'processing',
        ]);

        try {

            $content = $parser->parse(
                $document->disk,
                $document->path,
                $document->mime_type
            );

            $ragService->ingestDocument(
                source: $document->filename,
                content: $content,
                metadata: [
                    'document_id' => $document->id,
                    'filename' => $document->filename,
                ]
            );

            $document->update([
                'status' => 'indexed',
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
