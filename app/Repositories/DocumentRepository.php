<?php

namespace App\Repositories;

use App\Models\Document;

class DocumentRepository
{
    public function createOrUpdate(string $source, string $content, array $metadata = []): array
    {
        $document = Document::updateOrCreate(
            ['source' => $source],
            [
                'content' => $content,
                'metadata' => $metadata,
            ]
        );

        return $document->toArray();
    }

    public function findByIds(array $ids): array
    {
        $documents = Document::whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->filter(fn ($id) => $documents->has($id))
            ->map(fn ($id) => $documents->get($id)->only(['id', 'source', 'content', 'metadata']))
            ->values()
            ->toArray();
    }

    public function findBySource(string $source): ?array
    {
        $document = Document::where('source', $source)->first();

        return $document ? $document->toArray() : null;
    }
}
