<?php

use App\Models\Document;
use App\Models\VectorRecord;
use App\Repositories\VectorRepository;

it('uses cosine similarity for local pgvector-like distance comparisons', function () {
    
    $content = str_repeat('Local RAG document chunk testing. ', 50);
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

    $vector = generateRandomVector(768);

    VectorRecord::create([
        'document_id' => $document->id,
        'vector' => $vector,
        'metadata' => ['tag' => 'test-vector'],
    ]);

    $results = (new VectorRepository())->search($vector, 1);

    expect($results)->toHaveCount(1);
    expect($results[0]['document_id'])->toBe($document->id);
    expect($results[0]['score'])->toBeGreaterThan(0.999);
});
