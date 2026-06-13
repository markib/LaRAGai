<?php

namespace App\Services\Retrieval;

use App\Models\DocumentChunk;

class PostgresBm25Retriever
{
    public function search(
        string $query,
        int $limit = 10
    ): array {
        return DocumentChunk::query()
            ->selectRaw("
                id,
                document_id,
                content,
                ts_rank(
                    to_tsvector('english', content),
                    plainto_tsquery('english', ?)
                ) as score
            ", [$query])
            ->whereRaw("
                to_tsvector('english', content)
                @@
                plainto_tsquery('english', ?)
            ", [$query])
            ->orderByDesc('score')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
