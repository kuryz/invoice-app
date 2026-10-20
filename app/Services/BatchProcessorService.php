<?php

namespace App\Services;

class BatchProcessorService
{
    protected int $size;

    public function __construct(int $size = 100)
    {
        $this->size = $size;
    }

    public function process(callable $loader, callable $callback): void
    {
        $lastId = 0;
        while (true) {
            $rows = $loader($lastId, $this->size);
            if (!$rows || count($rows) === 0) {
                break;
            }
            foreach ($rows as $row) {
                $callback($row);
                $lastId = $row->id;
            }
            unset($rows);
            gc_collect_cycles();
        }
    }
}