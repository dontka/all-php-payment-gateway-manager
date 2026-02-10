<?php

declare(strict_types=1);

namespace PaymentGateway\Events;

use DateTimeImmutable;
use Exception;

/**
 * Event fired when a payment fails
 */
class PaymentFailedEvent
{
    /**
     * Event timestamp
     */
    private DateTimeImmutable $timestamp;

    /**
     * Exception that caused failure
     */
    private Exception $exception;

    /**
     * Original payment data
     */
    private array $data;

    /**
     * Constructor
     */
    public function __construct(
        Exception $exception,
        array $data = [],
        ?DateTimeImmutable $timestamp = null
    ) {
        $this->exception = $exception;
        $this->data = $data;
        $this->timestamp = $timestamp ?? new DateTimeImmutable();
    }

    /**
     * Get event timestamp
     */
    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    /**
     * Get exception
     */
    public function getException(): Exception
    {
        return $this->exception;
    }

    /**
     * Get error message
     */
    public function getErrorMessage(): string
    {
        return $this->exception->getMessage();
    }

    /**
     * Get original payment data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Convert event to array
     */
    public function toArray(): array
    {
        return [
            'event' => 'payment.failed',
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s.u'),
            'error' => $this->exception->getMessage(),
            'error_code' => $this->exception->getCode(),
            'data' => $this->data,
        ];
    }
}
