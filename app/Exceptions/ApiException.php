<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ApiException extends Exception
{
    /**
     * Additional data to include in the API response.
     */
    protected array $data = [];

    public function __construct(
        string $message = 'An API error occurred',
        int $statusCode = 500,
        array $data = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);

        $this->data = $data;
    }

    /**
     * HTTP status code.
     */
    public function statusCode(): int
    {
        return $this->getCode() ?: 500;
    }

    /**
     * Additional response data.
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Convert the exception to an API response array.
     */
    public function toArray(): array
    {
        return array_merge([
            'success' => false,
            'message' => $this->getMessage(),
        ], $this->data);
    }
}