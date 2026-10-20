<?php

namespace App\Services;

class CacheService
{
    private string $cacheDir;
    private int $defaultTTL;

    public function __construct(string $cacheDir = null, int $defaultTTL = 3600)
    {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../../storage/cache/';
        $this->defaultTTL = $defaultTTL;
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    /**
     * Get a cached value
     */
    public function get(string $key, $default = null)
    {
        $file = $this->getCacheFile($key);
        
        if (!$this->has($key)) {
            return $default;
        }
        
        $data = file_get_contents($file);
        $cached = unserialize($data);
        
        if ($cached['expires_at'] < time()) {
            $this->forget($key);
            return $default;
        }
        
        return $cached['value'];
    }

    /**
     * Store a value in cache
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTTL;
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
        ];
        
        $file = $this->getCacheFile($key);
        return file_put_contents($file, serialize($data)) !== false;
    }

    /**
     * Get a cached value or store it if not exists
     */
    public function remember(string $key, ?int $ttl, callable $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    /**
     * Check if a cache key exists and is valid
     */
    public function has(string $key): bool
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return false;
        }
        $data = file_get_contents($file);
        if ($data === false || $data === '') {
            $this->forget($key);
            return false;
        }
        $cached = @unserialize($data);
        // Invalid/corrupted cache data
        if ($cached === false || !is_array($cached)) {
            $this->forget($key);
            return false;
        }
        // Missing expiration value
        if (!isset($cached['expires_at'])) {
            $this->forget($key);
            return false;
        }
        // Check if expired
        if ($cached['expires_at'] < time()) {
            $this->forget($key);
            return false;
        }
        return true;
    }

    /**
     * Delete a cached value
     */
    public function forget(string $key): bool
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    /**
     * Clear all cache
     */
    public function clear(): bool
    {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    /**
     * Get cache file path
     */
    private function getCacheFile(string $key): string
    {
        $hash = md5($key);
        return $this->cacheDir . $hash . '.cache';
    }

    /**
     * Get cache statistics
     */
    public function stats(): array
    {
        $files = glob($this->cacheDir . '*.cache');
        $totalSize = 0;
        $count = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            $count++;
        }
        
        return [
            'total_files' => $count,
            'total_size' => $totalSize,
            'cache_dir' => $this->cacheDir,
        ];
    }

    /**
     * Remember forever (never expires)
     */
    public function rememberForever(string $key, callable $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->set($key, $value, 86400 * 365); // 1 year
        return $value;
    }

    /**
     * Increment a cached value
     */
    public function increment(string $key, int $amount = 1): int
    {
        $value = $this->get($key, 0);
        $value += $amount;
        $this->set($key, $value);
        return $value;
    }

    /**
     * Decrement a cached value
     */
    public function decrement(string $key, int $amount = 1): int
    {
        $value = $this->get($key, 0);
        $value -= $amount;
        $this->set($key, $value);
        return $value;
    }
}