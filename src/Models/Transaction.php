<?php

declare(strict_types=1);

namespace PaymentGateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * Transaction Model
 *
 * Represents individual transactions against payments
 */
class Transaction extends Model
{
    /**
     * Table name
     */
    protected $table = 'transactions';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'payment_id',
        'type',
        'status',
        'gateway_transaction_id',
        'reference_id',
        'amount',
        'currency',
        'request_data',
        'response_data',
        'error',
        'retry_count',
        'completed_at',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'request_data' => AsCollection::class,
        'response_data' => AsCollection::class,
        'retry_count' => 'integer',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Transaction types
     */
    public const TYPE_CHARGE = 'charge';
    public const TYPE_REFUND = 'refund';
    public const TYPE_CAPTURE = 'capture';
    public const TYPE_VERIFY = 'verify';

    /**
     * Transaction statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    /**
     * Get associated payment
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Check if transaction succeeded
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if transaction failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark transaction as successful
     */
    public function markSuccess(array $responseData = []): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'response_data' => $responseData,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markFailed(string $error = '', array $responseData = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error' => $error,
            'response_data' => $responseData,
        ]);
    }

    /**
     * Increment retry count
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Get transaction details
     */
    public function getDetails(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'retry_count' => $this->retry_count,
            'error' => $this->error,
            'created_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
