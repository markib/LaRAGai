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
            'source' => $this->faker->unique()->slug() . '.txt',
            'content' => $this->faker->paragraphs(4, true),
            'metadata' => [
                'imported_at' => now()->toISOString(),
            ],
        ];
    }
}
