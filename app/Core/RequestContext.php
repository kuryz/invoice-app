<?php
namespace App\Core;


class RequestContext
{
    private static ?Request $request = null;

    public static function set(Request $request): void
    {
        self::$request = $request;
    }

    public static function get(): Request
    {
        if (!self::$request) {
            throw new \RuntimeException('Request has not been initialized.');
        }

        return self::$request;
    }

    public static function clear(): void
    {
        self::$request = null;
    }
}
