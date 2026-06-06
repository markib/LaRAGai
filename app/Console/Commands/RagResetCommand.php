<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentChunk;
use Illuminate\Console\Command;
use App\Repositories\VectorRepositoryInterface;

class RagResetCommand extends Command
{
    protected $signature = 'rag:reset';

    protected $description = 'Clear documents, chunks and vector collection';

    public function handle(
        VectorRepositoryInterface $vectors
    ): int {
        $this->info('Clearing database...');

        DocumentChunk::truncate();
        Document::truncate();

        $this->info('Database cleared.');

        try {
            $this->info('Deleting Qdrant collection...');

            $vectors->clearCollection();

            $this->info('Qdrant collection deleted.');
        } catch (\Throwable $e) {
            $this->warn(
                'Failed to delete Qdrant collection: '
                    . $e->getMessage()
            );
        }

        $this->info('RAG reset completed.');

        return self::SUCCESS;
    }
}
