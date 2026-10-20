<?php
namespace App\Console\Contracts;

interface CronJobInterface
{
    public function handle(): void;
    public function batchSize(): int;
    public function name(): string;
}