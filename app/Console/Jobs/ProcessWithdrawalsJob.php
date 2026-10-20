<?php

namespace App\Console\Jobs;

use App\Console\Contracts\CronJobInterface;

class ProcessWithdrawalsJob implements CronJobInterface
{
    public function name(): string
    {
        return 'withdrawals';
    }

    public function batchSize(): int
    {
        return 50;
    }

    public function handle(): void
    {
        // auto withdrawals
    }
}