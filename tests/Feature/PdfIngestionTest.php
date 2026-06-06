<?php

use App\Jobs\IndexDocumentJob;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Repositories\VectorRepository;
use App\Services\DocumentParser;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

it('indexes a document and finds correct vector similarity result', function () {

    $this->mock(DocumentParser::class, function ($mock) {
        $mock->shouldReceive('parse')
            ->andReturn(str_repeat('Local RAG document chunk testing. ', 50));
    });
    Config::set('queue.default', 'sync');

    $vector = mockOllamaEmbeddings();
    mockOllamaCompletion('Mocked answer for PDF ingestion.');

    Storage::fake('local');

    $content = str_repeat('Local RAG document chunk testing. ', 50);

    // 1. Store fake file (important because parser uses disk/path)
    $path = Storage::disk('local')->put('documents/sample.pdf', $content);

    // 2. Create document record (matches your model)
    $document = Document::create([
        'filename' => 'sample.pdf',
        'original_filename' => 'sample.pdf',
        'disk' => 'local',
        'path' => 'documents/sample.pdf',
        'mime_type' => 'application/pdf',
        'size' => strlen($content),
        'status' => 'uploaded',
        'source' => 'sample.pdf',
    ]);

    // 3. Run job directly (no queue confusion)
    (new IndexDocumentJob($document->id))
        ->handle(
            app(\App\Services\RagService::class),
            app(\App\Services\DocumentParser::class)
        );

    // 4. Assertions
    expect(DocumentChunk::count())->toBeGreaterThan(1);

    expect(DocumentChunk::first()->embedding)
        ->toBeVectorLength(768);

    $results = (new VectorRepository())->search($vector, 1);

    expect($results)->toHaveCount(1);

    expect($results[0]['document_id'])->toBe($document->id);

    expect($results[0]['score'])->toBeGreaterThan(0.99);
});