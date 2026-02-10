<?php

declare(strict_types=1);

namespace PaymentGateway\Exceptions;

/**
 * Exception thrown when webhook processing fails
 */
class WebhookException extends PaymentException
{
    protected string $errorCode = 'WEBHOOK_ERROR';

    /**
     * Webhook event type
     */
    private string $eventType = '';

    /**
     * Payload that caused the error
     */
    private array $payload = [];

    /**
     * Set event type
     */
    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;
        return $this;
    }

    /**
     * Get event type
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }

    /**
     * Set webhook payload
     */
    public function setPayload(array $payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * Get webhook payload
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
