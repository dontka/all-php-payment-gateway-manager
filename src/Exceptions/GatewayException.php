<?php

declare(strict_types=1);

namespace PaymentGateway\Exceptions;

/**
 * Exception thrown by gateway implementations
 */
class GatewayException extends PaymentException
{
    protected string $errorCode = 'GATEWAY_ERROR';

    /**
     * Gateway name
     */
    private string $gateway = '';

    /**
     * Set gateway name
     */
    public function setGateway(string $gateway): self
    {
        $this->gateway = $gateway;
        return $this;
    }

    /**
     * Get gateway name
     */
    public function getGateway(): string
    {
        return $this->gateway;
    }

    /**
     * Convert to array including gateway info
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'gateway' => $this->gateway,
        ]);
    }
}
