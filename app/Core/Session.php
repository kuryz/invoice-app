<?php

namespace App\Core;

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function setFlash(string $key, string|array $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public function getFlash(string $key, $default = null)
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
    
        if (is_array($value)) {
            $messages = [];
    
            foreach ($value as $fieldMessages) {
                if (is_array($fieldMessages)) {
                    foreach ($fieldMessages as $message) {
                        $messages[] = (string) $message;
                    }
                } else {
                    $messages[] = (string) $fieldMessages;
                }
            }
    
            return implode("<br>", $messages);
        }
    
        return $value;
    }
    


    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public function destroy(): void
    {
        session_destroy();
    }
}