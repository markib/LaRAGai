<?php

namespace App\Console;

use App\Console\Commands\TestCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        TestCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Define scheduled commands here.
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
