<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'filename' => fake()->uuid() . '.pdf',
            'original_filename' => fake()->word() . '.pdf',
            'disk' => 'local',
            'path' => 'documents/sample.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1000, 50000),
            'status' => 'uploaded',
            'error_message' => null,
            'source' => fake()->word(),
        ];
    }
}
