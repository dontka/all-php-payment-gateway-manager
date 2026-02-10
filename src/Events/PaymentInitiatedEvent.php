<?php

declare(strict_types=1);

namespace PaymentGateway\Events;

use DateTimeImmutable;

/**
 * Event fired when a payment is initiated
 */
class PaymentInitiatedEvent
{
    /**
     * Event timestamp
     */
    private DateTimeImmutable $timestamp;

    /**
     * Payment data
     */
    private array $data;

    /**
     * Gateway source
     */
    private string $source;

    /**
     * Constructor
     */
    public function __construct(
        array $data,
        string $source,
        ?DateTimeImmutable $timestamp = null
    ) {
        $this->data = $data;
        $this->source = $source;
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
     * Get payment data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get source gateway
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Convert event to array
     */
    public function toArray(): array
    {
        return [
            'event' => 'payment.initiated',
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s.u'),
            'source' => $this->source,
            'data' => $this->data,
        ];
    }
}
