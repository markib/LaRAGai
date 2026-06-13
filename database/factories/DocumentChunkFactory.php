<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentChunk;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentChunkFactory extends Factory
{
    protected $model = DocumentChunk::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'chunk_index' => 0,
            'content' => $this->faker->paragraphs(2, true),
            'token_count' => rand(50, 200),
        ];
    }
}
