<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * Laravel Order Model Example
 *
 * Demonstrates how to work with payments in your models
 *
 * Usage:
 *   $order = Order::create([...]);
 *   if ($order->isPaid()) { ... }
 */
class Order extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'status',
        'gateway',
        'transaction_id',
        'refund_id',
        'completed_at',
        'refunded_at',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => AsCollection::class,
        'completed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * Check if payment is completed
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if order is refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): self
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now()
        ]);

        return $this;
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(): self
    {
        $this->update([
            'status' => self::STATUS_FAILED
        ]);

        return $this;
    }

    /**
     * Mark as refunded
     */
    public function markAsRefunded(): self
    {
        $this->update([
            'status' => self::STATUS_REFUNDED,
            'refunded_at' => now()
        ]);

        return $this;
    }

    /**
     * Get gateway used for this order
     */
    public function getGatewayAttribute(): string
    {
        return $this->gateway ?? 'paypal';
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get order status in human readable format
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => '⏳ Pending',
            self::STATUS_PROCESSING => '⌛ Processing',
            self::STATUS_COMPLETED => '✅ Completed',
            self::STATUS_FAILED => '❌ Failed',
            self::STATUS_CANCELLED => '🚫 Cancelled',
            self::STATUS_REFUNDED => '↩️ Refunded',
            default => 'Unknown'
        };
    }

    /**
     * Get all transactions for this order
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scope: get completed orders
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: get failed orders
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: get pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: get by gateway
     */
    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope: created in last N days
     */
    public function scopeRecentDays($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get summary for API response
     */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'gateway' => $this->gateway,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String()
        ];
    }
}
