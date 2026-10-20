<?php

namespace App\Core;

class Request
{
    private array $params = [];
    protected ?string $routeName = null;

    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function setRouteName(?string $name): void
    {
        $this->routeName = $name;
    }

    public function routeName(): ?string
    {
        return $this->routeName;
    }

    public function routeIs(string ...$patterns): bool
    {
        if (!$this->routeName) {
            return false;
        }
        foreach ($patterns as $pattern) {
            $regex = '#^' .
                str_replace('\*', '.*', preg_quote($pattern, '#'))
                . '$#';
            if (preg_match($regex, $this->routeName)) {
                return true;
            }
        }
        return false;
    }

    public function fullUrlWithQuery(array $query): string
    {
        // Current query parameters
        $current = $_GET;
        // Merge with new values
        $merged = array_merge($current, $query);
        // Remove null values (Laravel behavior)
        foreach ($merged as $key => $value) {
            if ($value === null) {
                unset($merged[$key]);
            }
        }
        // Current path without query string
        $path = strtok($_SERVER['REQUEST_URI'], '?');
        $queryString = http_build_query($merged);
        return $queryString
            ? $path . '?' . $queryString
            : $path;
    }

    public function getPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }
        
        return '/' . ltrim($path, '/');
    }

    public function getBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $body = [];

        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            $body = json_decode($input, true) ?? [];
        } elseif ($this->getMethod() === 'POST') {
            $body = $_POST;
        }elseif ($this->getMethod() === 'GET') {
            $body = $_GET;
        }

        return $body;
    }

    public function getQuery(): array
    {
        return $_GET;
    }

    public function all(): array
    {
        $data = $this->getBody();

        return array_merge($data, $_FILES);
    }

    public function getHeader(string $name): ?string
    {
        $headerName = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$headerName] ?? null;
    }

    public function header(string $name, $default = null): mixed
    {
        return $this->getHeader($name) ?? $default;
    }

    public function input(string $key, $default = null): mixed
    {
        $body = $this->getBody();
        return $body[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        $body = $this->getBody();
        return isset($body[$key]) && $body[$key] !== '';
        
    }

    public function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) 
            && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    public function file(string $key): ?array
    {
        if (!$this->hasFile($key)) {
            return null;
        }
        return $_FILES[$key];
    }

    public function query(string $key, $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function params(): array
    {
        return $this->params;
    }

    public function param(string $key, $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function setParams(array $params): void
    {
        $this->params = array_merge($this->params, $params);
    }

    public function getBearerToken(): ?string
    {
        $header = $this->getHeader('Authorization');
        
        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    public function getClientIp(): string
    {
        $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}