<?php

namespace App\Repositories;

use App\Models\Document;

class DocumentRepository
{
    /**
     * Fetch single document
     */
    public function find(int $id): ?Document
    {
        return Document::find($id);
    }

    /**
     * Fetch multiple documents by IDs
     */
    public function findByIds(array $ids): array
    {
        $ids = array_values(array_unique($ids));

        $documents = Document::whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->filter(fn ($id) => $documents->has($id))
            ->map(fn ($id) => $documents->get($id)->only([
                'id',
                'filename',
                'original_filename',
                'status',
                'created_at',
            ]))
            ->values()
            ->toArray();
    }
}
