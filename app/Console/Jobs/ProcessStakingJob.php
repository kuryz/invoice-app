<?php

namespace App\Console\Jobs;

use App\Console\Contracts\CronJobInterface;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BatchProcessorService;
use App\Services\CronLock;
use App\Services\CronLogger;
use App\Services\StakingMainService;
use App\Core\Database;
use Throwable;

class ProcessStakingJob implements CronJobInterface
{
    protected StakingMainService $stakingService;

    public function __construct()
    {
        $this->stakingService = new StakingMainService();
    }

    public function name(): string
    {
        return 'staking';
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function handle(): void
    {
        $lock = new CronLock('staking_process', 300);
        if (!$lock->acquire()) {
            return;
        }

        CronLogger::info('Staking started');
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
                    $this->processTransaction($transaction);
                }
            );
        } finally {
            $lock->release();
        }
        CronLogger::info('Process staking completed');
    }

    protected function processTransaction(Transaction $transaction): void
    {
        try {
            Database::transaction(function () use ($transaction) {
                $transaction = Transaction::find($transaction->id);
                if (!$transaction) {
                    return;
                }
                $user = User::find($transaction->user_id);
                if (!$user) {
                    return;
                }
                if (!$this->stakingService->alreadyCreditedToday($transaction)) {
                    // CronLogger::info('Process staking credit');
                    if ($this->stakingService->isEligibleForEarning($transaction)) {
                        $this->stakingService->creditDailyEarning(
                            $transaction,
                            $user
                        );
                    } else {
                        $this->stakingService->completeStake(
                            $transaction,
                            $user
                        );
                    }
                }
            });
        } catch (Throwable $e) {
            CronLogger::error($e->getMessage());
        }
    }
}