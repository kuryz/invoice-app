<?php

namespace App\Console\Jobs;

use App\Console\Contracts\CronJobInterface;
use App\Models\JackpotInvestment;
use App\Services\BatchProcessorService;
use App\Services\CronLogger;
use App\Services\JackpotStakingService;

class ProcessJackpotStakingJob implements CronJobInterface
{
    protected JackpotStakingService $service;

    public function __construct()
    {
        $this->service = new JackpotStakingService();
    }

    public function name(): string
    {
        return 'jackpot_staking';
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function shouldRun(): bool
    {
        return date('d') == 15
            && date('H:i') == '00:05';
    }

    public function handle(): void
    {
        CronLogger::info('Jackpot staking started');
        $processor = new BatchProcessorService($this->batchSize());
        $processor->process(
            function ($lastId, $limit) {
                return JackpotInvestment::query()
                    ->where('status', 'active')
                    ->where('investment_date', '<', date('Y-m-d H:i:s'))
                    ->where('id', '>', $lastId)
                    ->orderBy('id')
                    ->limit($limit)
                    ->get();
            },

            function ($investment) {
                $this->service->stake($investment);
            }
        );
        CronLogger::info('Jackpot staking finished');
    }
}