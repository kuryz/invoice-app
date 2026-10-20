<?php

namespace App\Console\Jobs;

use App\Console\Contracts\CronJobInterface;

class CleanupJob implements CronJobInterface
{
    public function name(): string
    {
        return 'cleanup';
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function handle(): void
    {
        // delete temp files
        // clear expired sessions
        // prune logs
    }
}