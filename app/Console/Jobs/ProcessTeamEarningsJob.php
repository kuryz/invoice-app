<?php

namespace App\Console\Jobs;

use App\Console\Contracts\CronJobInterface;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BatchProcessorService;
use App\Services\CronLock;
use App\Services\CronLogger;
use App\Services\TeamEarningService;

class ProcessTeamEarningsJob implements CronJobInterface
{
    protected TeamEarningService $service;

    public function __construct()
    {
        $this->service = new TeamEarningService();
    }

    public function name(): string
    {
        return 'team_earnings';
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function handle(): void
    {
        $lock = new CronLock('team_earning', 300);
        if (!$lock->acquire()) {
            return;
        }
        CronLogger::info('Team earning started');
        try {
            $processor = new BatchProcessorService($this->batchSize());
            $processor->process(
                function ($lastId, $limit) {
                    return Transaction::query()
                        ->where('remark', 'balance_staking')
                        ->where('status', 'ongoing')
                        ->where('id', '>', $lastId)
                        ->orderBy('id')
                        ->limit($limit)
                        ->get();
                },
                function ($transaction) {
                    $user = User::find($transaction->user_id);
                    if (!$user) {
                        return;
                    }
                    if (!$this->service->alreadyCreditedToday($transaction)) {
                        return;
                    }
                    $this->service->process($transaction, $user);
                }
            );
        } finally {
            $lock->release();
        }
        CronLogger::info('Team earning completed');
    }
}