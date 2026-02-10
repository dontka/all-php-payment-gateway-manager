<?php

declare(strict_types=1);

namespace PaymentGateway\Core;

use PaymentGateway\Exceptions\GatewayException;
use PaymentGateway\Exceptions\ConfigurationException;
use PaymentGateway\Traits\HasLogging;
use PaymentGateway\Traits\HasValidation;

/**
 * Central orchestrator for payment processing
 *
 * Manages all payment gateways, routes requests to appropriate
 * gateway implementations, and coordinates payment operations.
 */
class PaymentManager
{
    use HasLogging;
    use HasValidation;

    /**
     * Registered gateways
     */
    protected array $gateways = [];

    /**
     * Event listeners
     */
    protected array $listeners = [];

    /**
     * Configuration
     */
    protected array $config = [];

    /**
     * Active gateway instance
     */
    protected ?AbstractGateway $activeGateway = null;

    /**
     * Constructor
     *
     * @param array $config Manager configuration
     *
     * @throws ConfigurationException if configuration is invalid
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->validateConfiguration();
        $this->initializeGateways();
        $this->logInfo('PaymentManager initialized');
    }

    /**
     * Register a payment gateway
     *
     * @param string $name Gateway name
     * @param AbstractGateway $gateway Gateway instance
     *
     * @return self Fluent interface
     */
    public function registerGateway(string $name, AbstractGateway $gateway): self
    {
        $this->gateways[$name] = $gateway;
        $this->logInfo("Gateway registered: {$name}");
        return $this;
    }

    /**
     * Register a gateway from config
     *
     * @param string $name Gateway name
     * @param string $class Gateway class name
     * @param array $config Gateway configuration
     *
     * @return self Fluent interface
     *
     * @throws ConfigurationException if gateway class doesn't exist
     */
    public function registerGatewayClass(string $name, string $class, array $config): self
    {
        if (!class_exists($class)) {
            throw new ConfigurationException("Gateway class not found: {$class}");
        }

        try {
            $gateway = new $class($config);
            $this->registerGateway($name, $gateway);
        } catch (\Exception $e) {
            $this->logError("Failed to register gateway: {$name}", ['error' => $e->getMessage()]);
            throw new ConfigurationException("Failed to register gateway: {$name}", 0, $e);
        }

        return $this;
    }

    /**
     * Get a registered gateway
     *
     * @param string $name Gateway name
     *
     * @return AbstractGateway The gateway instance
     *
     * @throws GatewayException if gateway is not registered
     */
    public function gateway(string $name): AbstractGateway
    {
        if (!isset($this->gateways[$name])) {
            throw new GatewayException("Gateway not registered: {$name}");
        }

        $this->activeGateway = $this->gateways[$name];
        return $this->activeGateway;
    }

    /**
     * Get all registered gateways
     *
     * @return array Map of gateway names to instances
     */
    public function getGateways(): array
    {
        return $this->gateways;
    }

    /**
     * Check if a gateway is registered
     *
     * @param string $name Gateway name
     *
     * @return bool Gateway existence
     */
    public function hasGateway(string $name): bool
    {
        return isset($this->gateways[$name]);
    }

    /**
     * Get list of registered gateway names
     *
     * @return array List of gateway names
     */
    public function getGatewayNames(): array
    {
        return array_keys($this->gateways);
    }

    /**
     * Process a payment charge
     *
     * @param array $data Payment data
     *   - amount: float Payment amount (required)
     *   - currency: string Currency code (required)
     *   - gateway: string Gateway name (required first time)
     *   - customer: array Customer data
     *   - metadata: array Additional metadata
     *
     * @param ?string $gatewayName Override gateway name
     *
     * @return array Payment result
     *
     * @throws GatewayException if payment fails
     */
    public function charge(array $data, ?string $gatewayName = null): array
    {
        $this->logInfo('Payment charge initiated', ['gateway' => $gatewayName]);

        try {
            // Determine which gateway to use
            $gateway = $gatewayName
                ? $this->gateway($gatewayName)
                : $this->getActiveGateway();

            // Dispatch event
            $this->dispatch('payment:initiated', [
                'data' => $data,
                'gateway' => $gateway->getName(),
            ]);

            // Process charge
            $result = $gateway->charge($data);

            // Dispatch success event
            $this->dispatch('payment:success', $result);

            $this->logInfo('Payment successful', $result);
            return $result;
        } catch (\Exception $e) {
            $this->dispatch('payment:failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            $this->logError('Payment failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e;
        }
    }

    /**
     * Process a refund
     *
     * @param string $transactionId Transaction to refund
     * @param ?float $amount Refund amount (null for full refund)
     * @param ?string $gatewayName Gateway name
     * @param array $data Additional refund data
     *
     * @return array Refund result
     *
     * @throws GatewayException if refund fails
     */
    public function refund(
        string $transactionId,
        ?float $amount = null,
        ?string $gatewayName = null,
        array $data = []
    ): array {
        $this->logInfo('Refund initiated', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);

        try {
            $gateway = $gatewayName
                ? $this->gateway($gatewayName)
                : $this->getActiveGateway();

            $this->dispatch('refund:initiated', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'gateway' => $gateway->getName(),
            ]);

            $result = $gateway->refund($transactionId, $amount, $data);

            $this->dispatch('refund:success', $result);
            $this->logInfo('Refund successful', $result);

            return $result;
        } catch (\Exception $e) {
            $this->dispatch('refund:failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            $this->logError('Refund failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            throw $e;
        }
    }

    /**
     * Verify payment status
     *
     * @param string $transactionId Transaction to verify
     * @param ?string $gatewayName Gateway name
     *
     * @return array Payment status information
     *
     * @throws GatewayException if verification fails
     */
    public function verify(string $transactionId, ?string $gatewayName = null): array
    {
        try {
            $gateway = $gatewayName
                ? $this->gateway($gatewayName)
                : $this->getActiveGateway();

            $result = $gateway->verify($transactionId);
            $this->logInfo('Payment verified', $result);

            return $result;
        } catch (\Exception $e) {
            $this->logError('Payment verification failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            throw $e;
        }
    }

    /**
     * Handle webhook from payment provider
     *
     * @param string $gatewayName Gateway sending webhook
     * @param array $payload Webhook payload
     * @param array $headers Request headers
     *
     * @return array Webhook processing result
     *
     * @throws GatewayException if webhook processing fails
     */
    public function handleWebhook(
        string $gatewayName,
        array $payload,
        array $headers = []
    ): array {
        $this->logInfo('Webhook received', [
            'gateway' => $gatewayName,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);

        try {
            $gateway = $this->gateway($gatewayName);
            $result = $gateway->handleWebhook($payload, $headers);

            $this->dispatch('webhook:processed', $result);
            $this->logInfo('Webhook processed successfully', $result);

            return $result;
        } catch (\Exception $e) {
            $this->logError('Webhook processing failed', [
                'error' => $e->getMessage(),
                'gateway' => $gatewayName,
            ]);

            throw $e;
        }
    }

    /**
     * Register an event listener
     *
     * @param string $event Event name
     * @param callable $handler Event handler
     *
     * @return self Fluent interface
     */
    public function on(string $event, callable $handler): self
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $handler;
        return $this;
    }

    /**
     * Dispatch an event
     *
     * @param string $event Event name
     * @param array $data Event data
     */
    public function dispatch(string $event, array $data = []): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $handler) {
            try {
                $handler($data);
            } catch (\Exception $e) {
                $this->logError('Event handler error', [
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get the active gateway
     *
     * @return AbstractGateway Active gateway
     *
     * @throws GatewayException if no gateway is active
     */
    protected function getActiveGateway(): AbstractGateway
    {
        if ($this->activeGateway === null) {
            throw new GatewayException('No gateway set. Use gateway() or pass gatewayName to method.');
        }

        return $this->activeGateway;
    }

    /**
     * Validate manager configuration
     *
     * @throws ConfigurationException if configuration is invalid
     */
    protected function validateConfiguration(): void
    {
        // Configuration validation can be extended in subclasses
    }

    /**
     * Initialize gateways from configuration
     */
    protected function initializeGateways(): void
    {
        $gatewaysConfig = $this->config['gateways'] ?? [];

        foreach ($gatewaysConfig as $name => $config) {
            if (isset($config['class'])) {
                try {
                    $this->registerGatewayClass($name, $config['class'], $config);
                } catch (\Exception $e) {
                    $this->logError("Failed to initialize gateway: {$name}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Get configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value
     *
     * @return mixed Configuration value
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set configuration value
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     *
     * @return self Fluent interface
     */
    public function setConfig(string $key, mixed $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }
}
