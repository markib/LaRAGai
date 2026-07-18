<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Repositories\VectorRepositoryInterface;
use Illuminate\Console\Command;

class RagResetCommand extends Command
{
    protected $signature = 'rag:reset';

    protected $description = 'Clear documents, chunks and vector collection';

    public function handle(VectorRepositoryInterface $vectors): int
    {
        $this->info('Starting RAG reset...');

        try {
            $this->info('Clearing Qdrant collection first...');

            $vectors->clearCollection();

            $this->info('Qdrant cleared.');

            $this->info('Clearing database...');

            // safer than truncate in production RAG systems
            DocumentChunk::query()->delete();
            Document::query()->delete();

            $this->info('Database cleared.');
        } catch (\Throwable $e) {
            $this->error('RAG reset failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('RAG reset completed successfully.');

        return self::SUCCESS;
    }
}
