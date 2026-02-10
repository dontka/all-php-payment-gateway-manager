<?php

declare(strict_types=1);

namespace PaymentGateway\Traits;

use PaymentGateway\Exceptions\ValidationException;

/**
 * Trait for validation capabilities
 */
trait HasValidation
{
    /**
     * Validation rules
     */
    protected array $rules = [];

    /**
     * Validation errors
     */
    protected array $errors = [];

    /**
     * Validate data against rules
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     *
     * @return bool Validation result
     */
    public function validate(array $data, array $rules = []): bool
    {
        $this->errors = [];
        $rulesSet = !empty($rules) ? $rules : $this->rules;

        foreach ($rulesSet as $field => $fieldRules) {
            $this->validateField($field, $data[$field] ?? null, $fieldRules);
        }

        return empty($this->errors);
    }

    /**
     * Validate a single field
     */
    protected function validateField(string $field, mixed $value, string|array $rules): void
    {
        $rulesList = is_string($rules) ? explode('|', $rules) : $rules;

        foreach ($rulesList as $rule) {
            $this->applyRule($field, $value, trim($rule));
        }
    }

    /**
     * Apply a validation rule
     */
    protected function applyRule(string $field, mixed $value, string $rule): void
    {
        // Parse rule parameters (e.g., "string:min:3")
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $params = array_slice($parts, 1);

        match ($ruleName) {
            'required' => $this->validateRequired($field, $value),
            'email' => $this->validateEmail($field, $value),
            'numeric' => $this->validateNumeric($field, $value),
            'string' => $this->validateString($field, $value),
            'array' => $this->validateArray($field, $value),
            'min' => $this->validateMin($field, $value, $params[0] ?? null),
            'max' => $this->validateMax($field, $value, $params[0] ?? null),
            'regex' => $this->validateRegex($field, $value, $params[0] ?? null),
            'in' => $this->validateIn($field, $value, $params),
            default => null,
        };
    }

    /**
     * Required field validation
     */
    protected function validateRequired(string $field, mixed $value): void
    {
        if (empty($value) && $value !== '0' && $value !== 0) {
            $this->addError($field, 'required', "The field '{$field}' is required");
        }
    }

    /**
     * Email validation
     */
    protected function validateEmail(string $field, mixed $value): void
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'email', "The field '{$field}' must be a valid email");
        }
    }

    /**
     * Numeric validation
     */
    protected function validateNumeric(string $field, mixed $value): void
    {
        if (!empty($value) && !is_numeric($value)) {
            $this->addError($field, 'numeric', "The field '{$field}' must be numeric");
        }
    }

    /**
     * String validation
     */
    protected function validateString(string $field, mixed $value): void
    {
        if (!empty($value) && !is_string($value)) {
            $this->addError($field, 'string', "The field '{$field}' must be a string");
        }
    }

    /**
     * Array validation
     */
    protected function validateArray(string $field, mixed $value): void
    {
        if (!empty($value) && !is_array($value)) {
            $this->addError($field, 'array', "The field '{$field}' must be an array");
        }
    }

    /**
     * Min validation
     */
    protected function validateMin(string $field, mixed $value, ?string $min): void
    {
        if (!empty($value) && $min !== null) {
            $length = is_array($value) ? count($value) : strlen((string)$value);
            if ($length < (int)$min) {
                $this->addError($field, 'min', "The field '{$field}' must have a minimum of {$min}");
            }
        }
    }

    /**
     * Max validation
     */
    protected function validateMax(string $field, mixed $value, ?string $max): void
    {
        if (!empty($value) && $max !== null) {
            $length = is_array($value) ? count($value) : strlen((string)$value);
            if ($length > (int)$max) {
                $this->addError($field, 'max', "The field '{$field}' must not exceed {$max}");
            }
        }
    }

    /**
     * Regex validation
     */
    protected function validateRegex(string $field, mixed $value, ?string $pattern): void
    {
        if (!empty($value) && $pattern !== null && !preg_match($pattern, (string)$value)) {
            $this->addError($field, 'regex', "The field '{$field}' format is invalid");
        }
    }

    /**
     * In validation
     */
    protected function validateIn(string $field, mixed $value, array $values): void
    {
        if (!empty($value) && !in_array($value, $values, true)) {
            $this->addError($field, 'in', "The field '{$field}' must be one of: " . implode(', ', $values));
        }
    }

    /**
     * Add validation error
     */
    protected function addError(string $field, string $rule, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][$rule] = $message;
    }

    /**
     * Get all errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if there are any errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Clear errors
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Throw ValidationException if validation fails
     */
    public function validateOrFail(array $data, array $rules = []): void
    {
        if (!$this->validate($data, $rules)) {
            throw new ValidationException('Validation failed');
        }
    }
}
