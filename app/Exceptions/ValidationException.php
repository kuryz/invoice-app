<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ValidationException extends Exception
{
    /**
     * Validation errors.
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Create a new ValidationException instance.
     *
     * @param string $message
     * @param int $code
     * @param array $errors
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = 'Validation failed',
        int $code = 422,
        array $errors = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }
}