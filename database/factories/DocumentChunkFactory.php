<?php

namespace Database\Factories;

use App\Models\DocumentChunk;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentChunkFactory extends Factory
{
    protected $model = DocumentChunk::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'content' => $this->faker->paragraphs(2, true),
            'metadata' => [
                'chunk_index' => $this->faker->numberBetween(0, 10),
            ],
            'embedding' => array_map(
                fn () => $this->faker->randomFloat(8, -1, 1),
                range(1, 768)
            ),
        ];
    }
}
