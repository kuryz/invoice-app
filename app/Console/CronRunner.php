<?php

namespace App\Console;

use App\Services\CronLock;

class CronRunner
{
    public function run(): void
    {
        foreach ((new CronKernel())->jobs() as $job) {
            echo "Running {$job->name()}..." . PHP_EOL;
            $lock = new CronLock($job->name());
            if (!$lock->acquire()) {
                echo "Skipped (already running)." . PHP_EOL;
                continue;
            }
            $start = microtime(true);
            try {
                $job->handle();
                echo "Completed." . PHP_EOL;
            } catch (\Throwable $e) {
                echo $e->getMessage() . PHP_EOL;
            } finally {
                $lock->release();
                echo "Time: "
                    . round(microtime(true) - $start, 2)
                    . " sec"
                    . PHP_EOL;
            }
        }
    }
}