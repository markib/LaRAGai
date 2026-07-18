<?php

namespace App\Repositories;

use App\Models\Document;

class DocumentRepository
{
    /**
     * Fetch single document.
     */
    public function find(int $id): ?Document
    {
        return Document::query()->find($id);
    }

    /**
     * Fetch multiple documents by IDs.
     *
     * @param  array<int, int|string>                                                                                     $ids
     * @return array<int, array{id: int, filename: string, original_filename: string, status: string, created_at: mixed}>
     */
    public function findByIds(array $ids): array
    {
        $uniqueIds = array_values(array_unique($ids));

        $documents = Document::query()->whereIn('id', $uniqueIds)
            ->get()
            ->keyBy('id');

        return collect($uniqueIds)
            ->filter(fn ($id) => $documents->has($id))
            ->map(function ($id) use ($documents) {
                /** @var Document $document */
                $document = $documents->get($id);

                return $document->only([
                    'id',
                    'filename',
                    'original_filename',
                    'status',
                    'created_at',
                ]);
            })
            ->values()
            ->toArray();
    }
}
