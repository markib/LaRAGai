<?php

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Repositories\VectorRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;

it('uploads a pdf, splits the text into chunks, and finds the correct record with vector similarity', function () {
    Config::set('queue.default', 'sync');

    $vector = mockOllamaEmbeddings();
    mockOllamaCompletion('Mocked answer for PDF ingestion.');

    $content = str_repeat('Local RAG document chunk testing. ', 50);
    $file = UploadedFile::fake()->createWithContent('sample.pdf', $content, 'application/pdf');

    $response = $this->post('/api/rag/ingest', [
        'document' => $file,
        'source' => 'sample.pdf',
    ]);

    $response->assertStatus(200)->assertJson(['status' => 'queued']);

    $document = Document::first();
    expect($document)->not->toBeNull();
    expect(DocumentChunk::count())->toBeGreaterThan(1);
    expect(DocumentChunk::first()->embedding)->toBeVectorLength(768);

    $results = (new VectorRepository())->search($vector, 1);
    expect($results)->toHaveCount(1);
    expect($results[0]['document_id'])->toBe($document->id);
    expect($results[0]['score'])->toBeGreaterThan(0.99);
});
