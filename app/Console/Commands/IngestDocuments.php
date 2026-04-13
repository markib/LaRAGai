<?php

namespace App\Console\Commands;

use App\Jobs\IndexDocumentJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class IngestDocuments extends Command
{
    protected $signature = 'rag:ingest {path : File or directory path} {--source= : Optional document source identifier}';
    protected $description = 'Ingest documents from a file or directory into the RAG index.';

    public function handle(): int
    {
        $path = $this->argument('path');
        $source = $this->option('source');

        if (!File::exists($path)) {
            $this->error("Path not found: {$path}");
            return 1;
        }

        $files = File::isDirectory($path) ? File::files($path) : [$path];

        foreach ($files as $file) {
            $text = File::get($file);
            $documentSource = $source ?: ($file instanceof \SplFileInfo ? $file->getFilename() : basename($path));
            IndexDocumentJob::dispatch($documentSource, $text, ['path' => (string) $file]);
            $this->info("Queued ingestion for {$documentSource}");
        }

        $this->info('Document ingestion jobs queued successfully.');

        return 0;
    }
}
