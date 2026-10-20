<?php

namespace App\Services;

class Logger
{
    protected string $path;

    public function __construct()
    {
        $this->path = BASE_PATH . '/storage/logs';

        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    protected function write(string $level, string $message, array $context = []): void
    {
        $file = $this->path . '/' . date('Y-m-d') . '.log';

        $line = sprintf(
            "[%s] [%s] %s",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message
        );

        if (!empty($context)) {
            $line .= PHP_EOL . json_encode(
                $context,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );
        }

        $line .= PHP_EOL . str_repeat('-', 80) . PHP_EOL;

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->write('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->write('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->write('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->write('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->write('debug', $message, $context);
    }
}