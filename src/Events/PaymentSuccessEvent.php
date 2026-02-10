<?php

declare(strict_types=1);

namespace PaymentGateway\Events;

use DateTimeImmutable;

/**
 * Event fired when a payment is successfully completed
 */
class PaymentSuccessEvent
{
    /**
     * Event timestamp
     */
    private DateTimeImmutable $timestamp;

    /**
     * Payment result data
     */
    private array $result;

    /**
     * Constructor
     */
    public function __construct(
        array $result,
        ?DateTimeImmutable $timestamp = null
    ) {
        $this->result = $result;
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
     * Get payment result
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Get transaction ID
     */
    public function getTransactionId(): ?string
    {
        return $this->result['transaction_id'] ?? null;
    }

    /**
     * Get payment amount
     */
    public function getAmount(): ?float
    {
        return $this->result['amount'] ?? null;
    }

    /**
     * Convert event to array
     */
    public function toArray(): array
    {
        return [
            'event' => 'payment.success',
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s.u'),
            'result' => $this->result,
        ];
    }
}
