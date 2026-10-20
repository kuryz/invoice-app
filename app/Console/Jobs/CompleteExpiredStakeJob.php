<?php

namespace App\Console\Jobs;

use App\Console\Contracts\CronJobInterface;

class CompleteExpiredStakeJob implements CronJobInterface
{
    public function name(): string
    {
        return 'complete_expired_stake';
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function handle(): void
    {
        // close completed plans
    }
}