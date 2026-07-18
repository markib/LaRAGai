<?php

namespace App\Services\Retrieval;

use App\DTO\Bm25Result;
use App\Models\DocumentChunk;
use Illuminate\Support\Collection;

class PostgresBm25Retriever
{
    /**
     * @return array<int, Bm25Result>
     */
    public function search(string $query, int $limit = 10): array
    {
        /** @var Collection<int, object{id:int, document_id:int, content:string, score:float}> $results */
        $results = DocumentChunk::query()
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
            ->get();

        return $results->map(
            fn (object $row): Bm25Result => new Bm25Result(
                id: (int) $row->id,
                documentId: (int) $row->document_id,
                content: (string) $row->content,
                score: (float) $row->score,
            )
        )->all();
    }
}
