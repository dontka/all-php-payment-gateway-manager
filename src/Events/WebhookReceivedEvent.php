<?php

declare(strict_types=1);

namespace PaymentGateway\Events;

use DateTimeImmutable;

/**
 * Event fired when a webhook is received
 */
class WebhookReceivedEvent
{
    /**
     * Event timestamp
     */
    private DateTimeImmutable $timestamp;

    /**
     * Gateway name
     */
    private string $gateway;

    /**
     * Webhook event type
     */
    private string $eventType;

    /**
     * Webhook payload
     */
    private array $payload;

    /**
     * Request headers
     */
    private array $headers;

    /**
     * Constructor
     */
    public function __construct(
        string $gateway,
        string $eventType,
        array $payload,
        array $headers = [],
        ?DateTimeImmutable $timestamp = null
    ) {
        $this->gateway = $gateway;
        $this->eventType = $eventType;
        $this->payload = $payload;
        $this->headers = $headers;
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
     * Get gateway name
     */
    public function getGateway(): string
    {
        return $this->gateway;
    }

    /**
     * Get webhook event type
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }

    /**
     * Get webhook payload
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Get request headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get a specific header value
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        return $this->headers[$name] ?? $default;
    }

    /**
     * Convert event to array
     */
    public function toArray(): array
    {
        return [
            'event' => 'webhook.received',
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s.u'),
            'gateway' => $this->gateway,
            'event_type' => $this->eventType,
            'payload' => $this->payload,
        ];
    }
}
