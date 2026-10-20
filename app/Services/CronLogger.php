<?php

namespace App\Services;

class CronLogger
{
    protected static string $logFile;

    protected static function init(): void
    {
        self::$logFile = BASE_PATH . '/storage/logs/cron.log';

        $dir = dirname(self::$logFile);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public static function info(string $message): void
    {
        self::write('INFO', $message);
    }

    public static function warning(string $message): void
    {
        self::write('WARNING', $message);
    }

    public static function error(string $message): void
    {
        self::write('ERROR', $message);
    }

    protected static function write(string $level, string $message): void
    {
        self::init();

        file_put_contents(
            self::$logFile,
            sprintf(
                "[%s] [%s] %s%s",
                date('Y-m-d H:i:s'),
                $level,
                $message,
                PHP_EOL
            ),
            FILE_APPEND
        );
    }
}