<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class CronLock
{
    protected PDO $db;
    protected string $name;
    protected int $timeout;

    public function __construct(string $name, int $timeout = 1)
    {
        $this->db = Database::getInstance()->getConnection(); // Adjust to your Database class
        $this->name = 'cron_' . md5($name);
        $this->timeout = $timeout;
    }

    /**
     * Attempt to acquire the lock.
     */
    public function acquire(): bool
    {
        $stmt = $this->db->prepare(
            'SELECT GET_LOCK(?, ?) AS lock_status'
        );

        $stmt->execute([
            $this->name,
            $this->timeout
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($result['lock_status']) && (int)$result['lock_status'] === 1;
    }

    /**
     * Release the lock.
     */
    public function release(): bool
    {
        $stmt = $this->db->prepare(
            'SELECT RELEASE_LOCK(?) AS released'
        );

        $stmt->execute([
            $this->name
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($result['released']) && (int)$result['released'] === 1;
    }

    /**
     * Check if another process currently owns the lock.
     */
    public function isLocked(): bool
    {
        $stmt = $this->db->prepare(
            'SELECT IS_FREE_LOCK(?) AS free_lock'
        );

        $stmt->execute([
            $this->name
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($result['free_lock']) && (int)$result['free_lock'] === 0;
    }

    /**
     * Execute a callback while holding the lock.
     */
    public function run(callable $callback)
    {
        if (!$this->acquire()) {
            return false;
        }

        try {
            return $callback();
        } finally {
            $this->release();
        }
    }
}