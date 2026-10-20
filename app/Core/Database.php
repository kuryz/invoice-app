<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $connection;
    private array $config;

    private function __construct()
    {
        $this->config = require __DIR__ . '/../../config/database.php';
        $this->connect();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void
    {
        $config = $this->config['connections'][$this->config['default']];
        
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function prepare(string $sql): \PDOStatement
    {
        return $this->connection->prepare($sql);
    }

    public function execute(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function query(string $sql): \PDOStatement
    {
        return $this->connection->query($sql);
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    public static function transaction(callable $callback)
    {
        $db = self::getInstance();
        $alreadyInTransaction = $db->getConnection()->inTransaction();
        if (!$alreadyInTransaction) {
            $db->beginTransaction();
        }
        try {
            $result = $callback();
            if (!$alreadyInTransaction) {
                $db->commit();
            }
            return $result;
        } catch (\Throwable $e) {
            if (!$alreadyInTransaction && $db->getConnection()->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    public function escape(string $value): string
    {
        return $this->connection->quote($value);
    }
}