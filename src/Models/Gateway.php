<?php

declare(strict_types=1);

namespace PaymentGateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * Gateway Model
 *
 * Represents available payment gateway configurations
 */
class Gateway extends Model
{
    /**
     * Table name
     */
    protected $table = 'gateways';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'name',
        'code',
        'type',
        'enabled',
        'configuration',
        'features',
        'support_email',
        'webhook_url',
        'supported_currencies',
        'supported_countries',
        'description',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'enabled' => 'boolean',
        'configuration' => AsCollection::class,
        'features' => AsCollection::class,
        'supported_currencies' => AsCollection::class,
        'supported_countries' => AsCollection::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Gateway types
     */
    public const TYPE_CARD = 'card';
    public const TYPE_WALLET = 'wallet';
    public const TYPE_BANK_TRANSFER = 'bank_transfer';
    public const TYPE_CRYPTO = 'crypto';
    public const TYPE_MOBILE_MONEY = 'mobile_money';

    /**
     * Gateway features
     */
    public const FEATURE_CHARGE = 'charge';
    public const FEATURE_REFUND = 'refund';
    public const FEATURE_VERIFY = 'verify';
    public const FEATURE_WEBHOOK = 'webhook';
    public const FEATURE_SUBSCRIPTIONS = 'subscriptions';

    /**
     * Check if gateway is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if gateway supports a specific feature
     */
    public function supportsFeature(string $feature): bool
    {
        return $this->features?->contains($feature) ?? false;
    }

    /**
     * Check if gateway supports a currency
     */
    public function supportsCurrency(string $currency): bool
    {
        return $this->supported_currencies?->contains(strtoupper($currency)) ?? false;
    }

    /**
     * Check if gateway supports a country
     */
    public function supportsCountry(string $country): bool
    {
        return $this->supported_countries?->contains(strtoupper($country)) ?? false;
    }

    /**
     * Get gateway configuration
     */
    public function getConfiguration(): array
    {
        return $this->configuration?->toArray() ?? [];
    }

    /**
     * Get gateway features list
     */
    public function getFeatures(): array
    {
        return $this->features?->toArray() ?? [];
    }

    /**
     * Enable gateway
     */
    public function enable(): void
    {
        $this->update(['enabled' => true]);
    }

    /**
     * Disable gateway
     */
    public function disable(): void
    {
        $this->update(['enabled' => false]);
    }

    /**
     * Get gateway info
     */
    public function getInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'enabled' => $this->enabled,
            'features' => $this->getFeatures(),
            'supported_currencies' => $this->supported_currencies?->toArray() ?? [],
            'supported_countries' => $this->supported_countries?->toArray() ?? [],
        ];
    }
}
