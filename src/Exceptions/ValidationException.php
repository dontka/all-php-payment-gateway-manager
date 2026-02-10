<?php

declare(strict_types=1);

namespace PaymentGateway\Exceptions;

/**
 * Exception thrown when validation fails
 */
class ValidationException extends PaymentException
{
    protected string $errorCode = 'VALIDATION_ERROR';

    /**
     * Field that failed validation
     */
    private string $field = '';

    /**
     * Validation rule that failed
     */
    private string $rule = '';

    /**
     * Set field name
     */
    public function setField(string $field): self
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Get field name
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Set validation rule
     */
    public function setRule(string $rule): self
    {
        $this->rule = $rule;
        return $this;
    }

    /**
     * Get validation rule
     */
    public function getRule(): string
    {
        return $this->rule;
    }

    /**
     * Convert to array including field info
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'field' => $this->field,
            'rule' => $this->rule,
        ]);
    }
}
