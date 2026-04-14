<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TestCommand extends Command
{
    protected $signature = 'test {--colors=always} {--filter=} {--parallel} {--testsuite=}';

    protected $description = 'Run the application tests using Pest or PHPUnit.';

    public function handle(): int
    {
        $binary = $this->resolveTestBinary();
        $options = $this->buildOptions();

        $command = PHP_BINARY . ' ' . escapeshellarg($binary) . ' ' . implode(' ', array_map('escapeshellarg', $options));
        $process = Process::fromShellCommandline($command, base_path(), [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => base_path('database/testing.sqlite'),
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
            'RAG_VECTOR_STORE' => 'local',
        ]);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer): void {
            $this->output->write($buffer);
        });

        return $process->getExitCode() ?: 0;
    }

    protected function resolveTestBinary(): string
    {
        $pest = base_path('vendor/bin/pest');
        $phpunit = base_path('vendor/bin/phpunit');

        if (file_exists($pest)) {
            return $pest;
        }

        if (file_exists($phpunit)) {
            return $phpunit;
        }

        $this->error('Neither Pest nor PHPUnit is installed. Run composer install first.');

        return '';
    }

    protected function buildOptions(): array
    {
        $options = [];

        if ($this->option('colors')) {
            $options[] = '--colors=' . $this->option('colors');
        }

        if ($this->option('filter')) {
            $options[] = '--filter=' . $this->option('filter');
        }

        if ($this->option('parallel')) {
            $options[] = '--parallel';
        }

        if ($this->option('testsuite')) {
            $options[] = '--testsuite=' . $this->option('testsuite');
        }

        return $options;
    }
}
