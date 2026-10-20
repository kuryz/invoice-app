<?php
namespace App\Console;

use App\Console\Jobs\CleanupJob;
use App\Console\Jobs\CompleteExpiredStakeJob;
use App\Console\Jobs\ProcessStakingJob;
use App\Console\Jobs\ProcessTeamEarningsJob;
use App\Console\Jobs\ProcessJackpotStakingJob;

class CronKernel
{
    public function jobs(): array
    {
        return [
            // new ProcessStakingJob(),
            // new ProcessTeamEarningsJob(),
            // new CompleteExpiredStakeJob(),
            // new ProcessJackpotStakingJob(),
            new CleanupJob(),
        ];
    }
}