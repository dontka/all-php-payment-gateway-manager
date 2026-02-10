<?php

declare(strict_types=1);

namespace PaymentGateway\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ApiKey Model
 *
 * Manages encrypted API keys for gateway authentication
 */
class ApiKey extends Model
{
    /**
     * Table name
     */
    protected $table = 'api_keys';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'gateway',
        'key_type',
        'key_value',
        'environment',
        'active',
        'created_by',
        'last_used_at',
    ];

    /**
     * Hidden attributes
     */
    protected $hidden = ['key_value'];

    /**
     * Attribute casting
     */
    protected $casts = [
        'active' => 'boolean',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Key types
     */
    public const TYPE_PUBLIC = 'public';
    public const TYPE_SECRET = 'secret';
    public const TYPE_WEBHOOK_SECRET = 'webhook_secret';

    /**
     * Environments
     */
    public const ENV_SANDBOX = 'sandbox';
    public const ENV_PRODUCTION = 'production';

    /**
     * Disable mass assignment of key_value
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->makeHidden(['key_value']);
    }

    /**
     * Check if key is active
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Activate key
     */
    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    /**
     * Deactivate key
     */
    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    /**
     * Update last used timestamp
     */
    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get key value (use with care - this reveals the actual key)
     */
    public function getKeyValue(): string
    {
        return decrypt($this->attributes['key_value']);
    }

    /**
     * Set key value (will be encrypted)
     */
    public function setKeyValue(string $value): void
    {
        $this->attributes['key_value'] = encrypt($value);
    }
}
