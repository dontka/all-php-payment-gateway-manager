<?php

declare(strict_types=1);

namespace PaymentGateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * WebhookLog Model
 *
 * Tracks all webhook events received from payment gateways
 */
class WebhookLog extends Model
{
    /**
     * Table name
     */
    protected $table = 'webhook_logs';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'payment_id',
        'gateway',
        'event_type',
        'transaction_id',
        'payload',
        'headers',
        'status',
        'error',
        'signature',
        'signature_valid',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'payload' => AsCollection::class,
        'headers' => AsCollection::class,
        'signature_valid' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Webhook statuses
     */
    public const STATUS_RECEIVED = 'received';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    /**
     * Get associated payment
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Check if webhook was processed successfully
     */
    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    /**
     * Mark webhook as processed
     */
    public function markProcessed(): void
    {
        $this->update(['status' => self::STATUS_PROCESSED]);
    }

    /**
     * Mark webhook as failed
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error' => $error,
        ]);
    }

    /**
     * Get webhook summary
     */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'gateway' => $this->gateway,
            'event_type' => $this->event_type,
            'transaction_id' => $this->transaction_id,
            'status' => $this->status,
            'signature_valid' => $this->signature_valid,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
