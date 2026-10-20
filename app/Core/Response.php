<?php

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private array $data = [];

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        \App\Services\CronLogger::info(
        'Response headers: ' . json_encode($this->headers)
    );
        
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        
        header('Content-Type: application/json');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function success(array $data = [], string $message = 'Success', int $statusCode = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        $this->json($response, $statusCode);
    }

    public function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
}