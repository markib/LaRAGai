<?php

use App\Jobs\IndexDocumentJob;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\DocumentEmbedding;
use App\Repositories\QdrantVectorRepository;
use App\Repositories\VectorRepository;
use App\Services\DocumentParser;
use Illuminate\Support\Facades\Storage;

it('indexes a document and stores chunks and embeddings', function () {

    Storage::fake('local');

    // Required because parser checks file existence
    Storage::disk('local')->put(
        'documents/sample.pdf',
        'fake pdf content'
    );

    $vector = mockOllamaEmbeddings();

    mockOllamaCompletion(
        'Mocked answer for PDF ingestion.'
    );

    $this->mock(DocumentParser::class, function ($mock) {
        $mock->shouldReceive('parse')
            ->once()
            ->andReturn(
                str_repeat(
                    'Local RAG document chunk testing. ',
                    50
                )
            );
    });

    $document = Document::create([
        'filename' => 'sample.pdf',
        'original_filename' => 'sample.pdf',
        'disk' => 'local',
        'path' => 'documents/sample.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1234,
        'status' => 'uploaded',
    ]);

    app(IndexDocumentJob::class, [
        'documentId' => $document->id,
    ])->handle(
        app(\App\Services\RagService::class),
        app(DocumentParser::class)
    );

    $document->refresh();

    expect($document->status)
        ->toBe('indexed');

    expect(DocumentChunk::count())
        ->toBeGreaterThan(1);

    expect(DocumentEmbedding::count())
        ->toBe(DocumentChunk::count());

    $chunk = DocumentChunk::first();

    expect($chunk)->not->toBeNull();

    $embedding = DocumentEmbedding::first();

    expect($embedding)->not->toBeNull();

    expect($embedding->embedding)
        ->toBeArray();

    expect(count($embedding->embedding))
        ->toBe(768);

    $results = app(QdrantVectorRepository::class)
        ->search($vector, 1);

    expect($results)->toHaveCount(1);

    expect($results[0]['document_id'])
        ->toBe($document->id);

    expect($results[0]['score'])
        ->toBeGreaterThan(0.90);
});
