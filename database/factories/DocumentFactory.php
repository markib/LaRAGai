<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Document>
     */
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'filename' => fake()->uuid().'.pdf',
            'original_filename' => fake()->word().'.pdf',
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
