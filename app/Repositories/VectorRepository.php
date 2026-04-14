<?php

namespace App\Repositories;

use App\Models\VectorRecord;

class VectorRepository implements VectorRepositoryInterface
{
    public function saveEmbedding(int $documentId, array $vector, array $metadata = []): array
    {
        $record = VectorRecord::create([
            'document_id' => $documentId,
            'vector' => $vector,
            'metadata' => $metadata,
        ]);

        return $record->toArray();
    }

    public function search(array $vector, int $limit = 5): array
    {
        if (config('database.default') === 'pgsql') {
            $vectorLiteral = '[' . implode(',', array_map(fn ($item) => is_numeric($item) ? $item : floatval($item), $vector)) . ']';

            return VectorRecord::selectRaw('document_id, 1 / (1 + (vector <=> ?::vector)) as score', [$vectorLiteral])
                ->orderByDesc('score')
                ->limit($limit)
                ->get()
                ->map(fn ($record) => [
                    'document_id' => $record->document_id,
                    'score' => (float) $record->score,
                ])
                ->toArray();
        }

        return VectorRecord::all()
            ->map(fn ($record) => [
                'document_id' => $record->document_id,
                'score' => $this->cosineSimilarity($vector, $record->vector),
            ])
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->toArray();
    }

    public function getPoint(int $documentId): ?array
    {
        $record = VectorRecord::where('document_id', $documentId)->first();

        return $record ? $record->toArray() : null;
    }

    public function deletePoint(int $documentId): bool
    {
        return VectorRecord::where('document_id', $documentId)->delete() > 0;
    }

    public function clearCollection(): bool
    {
        VectorRecord::truncate();

        return true;
    }

    protected function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($vectorA as $index => $value) {
            $dot += ($value * ($vectorB[$index] ?? 0));
            $normA += $value * $value;
            $normB += ($vectorB[$index] ?? 0) * ($vectorB[$index] ?? 0);
        }

        return $normA > 0 && $normB > 0 ? $dot / (sqrt($normA) * sqrt($normB)) : 0.0;
    }
}
