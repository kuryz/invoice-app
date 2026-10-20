<?php

namespace App\Services;

class ValidatorService
{
    protected array $data;
    protected array $rules;
    protected array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function validate(): bool
    {
        foreach ($this->rules as $field => $rules) {

            $value = $this->data[$field] ?? null;

            if (is_string($rules)) {
                $rules = explode('|', $rules);
            }

            foreach ($rules as $rule) {

                $params = [];

                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                    $params = explode(',', $param);
                }

                switch ($rule) {

                    case 'required':
                        if ($value === null || $value === '') {
                            $this->addError($field, "$field is required");
                        }
                        break;

                    case 'nullable':
                        break;

                    case 'numeric':
                        if ($value !== null && !is_numeric($value)) {
                            $this->addError($field, "$field must be numeric");
                        }
                        break;

                    case 'integer':
                        if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $this->addError($field, "$field must be an integer");
                        }
                        break;

                    case 'email':
                        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($field, "$field must be a valid email");
                        }
                        break;

                    case 'string':
                        if ($value !== null && !is_string($value)) {
                            $this->addError($field, "$field must be a string");
                        }
                        break;

                    case 'min':
                        if (is_numeric($value)) {
                            if ($value < $params[0]) {
                                $this->addError($field, "$field must be at least {$params[0]}");
                            }
                        } elseif (strlen((string)$value) < $params[0]) {
                            $this->addError($field, "$field must be at least {$params[0]} characters");
                        }
                        break;

                    case 'max':
                        if (is_numeric($value)) {
                            if ($value > $params[0]) {
                                $this->addError($field, "$field may not be greater than {$params[0]}");
                            }
                        } elseif (strlen((string)$value) > $params[0]) {
                            $this->addError($field, "$field may not be greater than {$params[0]} characters");
                        }
                        break;

                    case 'in':
                        if (!in_array($value, $params, true)) {
                            $this->addError($field, "$field contains an invalid value");
                        }
                        break;

                    case 'regex':
                        if ($value !== null && !preg_match($params[0], $value)) {
                            $this->addError($field, "$field format is invalid");
                        }
                        break;
                }
            }
        }

        return empty($this->errors);
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return !$this->validate();
    }

    public function passes(): bool
    {
        return $this->validate();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $messages) {
            return $messages[0];
        }

        return null;
    }
}