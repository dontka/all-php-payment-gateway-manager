<?php

declare(strict_types=1);

namespace PaymentGateway\Exceptions;

use Exception;

/**
 * Base exception for all payment-related errors
 */
class PaymentException extends Exception
{
    /**
     * Error code for categorization
     */
    protected string $errorCode = 'PAYMENT_ERROR';

    /**
     * Additional context data
     */
    protected array $context = [];

    /**
     * Constructor
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get context data
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set context data
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Convert exception to array for logging/response
     */
    public function toArray(): array
    {
        return [
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
        ];
    }
}
