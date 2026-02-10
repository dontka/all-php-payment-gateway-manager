<?php

declare(strict_types=1);

namespace PaymentGateway\Exceptions;

/**
 * Exception thrown when configuration is invalid
 */
class ConfigurationException extends PaymentException
{
    protected string $errorCode = 'CONFIG_ERROR';

    /**
     * Configuration key that caused the error
     */
    private string $configKey = '';

    /**
     * Set config key
     */
    public function setConfigKey(string $key): self
    {
        $this->configKey = $key;
        return $this;
    }

    /**
     * Get config key
     */
    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'config_key' => $this->configKey,
        ]);
    }
}
