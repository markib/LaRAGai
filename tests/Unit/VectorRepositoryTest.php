<?php

use App\Models\Document;
use App\Models\VectorRecord;
use App\Repositories\VectorRepository;

it('uses cosine similarity for local pgvector-like distance comparisons', function () {
    $document = Document::factory()->create();
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
