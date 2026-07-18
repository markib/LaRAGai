<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\DocumentEmbedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentEmbedding>
 */
class DocumentEmbeddingFactory extends Factory
{
    protected $model = DocumentEmbedding::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'chunk_id' => DocumentChunk::factory(),
            'model' => fake()->randomElement([
                'nomic-embed-text:latest',
                'text-embedding-3-small',
                'text-embedding-ada-002',
            ]),
        ];
    }
}
