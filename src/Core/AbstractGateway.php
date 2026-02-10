<?php

declare(strict_types=1);

namespace PaymentGateway\Core;

use PaymentGateway\Exceptions\GatewayException;
use PaymentGateway\Exceptions\ValidationException;

/**
 * Abstract base class for all payment gateways
 *
 * All gateway implementations must extend this class and implement
 * the required methods for their specific payment provider.
 */
abstract class AbstractGateway
{
    /**
     * Gateway name/identifier
     */
    protected string $name;

    /**
     * Gateway configuration
     */
    protected array $config = [];

    /**
     * API Client instance
     */
    protected mixed $client = null;

    /**
     * Constructor
     *
     * @param array $config Configuration for the gateway
     * @throws GatewayException if configuration is invalid
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->validateConfiguration();
        $this->initializeClient();
    }

    /**
     * Get the gateway name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Charge/Process a payment
     *
     * @param array $data Payment data with structure:
     *   - amount: float Payment amount
     *   - currency: string ISO 4217 currency code
     *   - customer: array Customer data
     *   - metadata: array Additional metadata
     *
     * @return array Result with structure:
     *   - success: bool Payment success status
     *   - transaction_id: string Transaction identifier
     *   - amount: float Charged amount
     *   - currency: string Currency code
     *   - status: string Payment status (pending, completed, failed)
     *   - metadata: array Response metadata
     *
     * @throws GatewayException if payment fails
     */
    abstract public function charge(array $data): array;

    /**
     * Refund a payment
     *
     * @param string $transactionId Original transaction ID
     * @param float|null $amount Partial refund amount (null for full refund)
     * @param array $data Additional refund data
     *
     * @return array Result with structure:
     *   - success: bool Refund success status
     *   - refund_id: string Refund transaction ID
     *   - amount: float Refunded amount
     *   - status: string Refund status
     *
     * @throws GatewayException if refund fails
     */
    abstract public function refund(
        string $transactionId,
        ?float $amount = null,
        array $data = []
    ): array;

    /**
     * Verify a payment status
     *
     * @param string $transactionId Transaction identifier
     *
     * @return array Payment status with structure:
     *   - status: string Current payment status
     *   - amount: float Payment amount
     *   - currency: string Currency code
     *   - timestamp: int Unix timestamp
     *   - metadata: array Additional info
     *
     * @throws GatewayException if verification fails
     */
    abstract public function verify(string $transactionId): array;

    /**
     * Handle webhook callback from payment provider
     *
     * @param array $payload Webhook payload from provider
     * @param array $headers Request headers
     *
     * @return array Result with structure:
     *   - success: bool Webhook processing success
     *   - event_type: string Type of webhook event
     *   - transaction_id: string Associated transaction ID
     *   - status: string Update status
     *
     * @throws GatewayException if webhook is invalid
     */
    abstract public function handleWebhook(array $payload, array $headers = []): array;

    /**
     * Get available payment methods for this gateway
     *
     * @return array List of available methods
     */
    public function getPaymentMethods(): array
    {
        return $this->config['payment_methods'] ?? [];
    }

    /**
     * Check if gateway is properly configured
     *
     * @return bool Configuration validity
     */
    public function isConfigured(): bool
    {
        try {
            $this->validateConfiguration();
            return true;
        } catch (GatewayException) {
            return false;
        }
    }

    /**
     * Validate gateway configuration
     *
     * Override this method in child classes to validate
     * their specific configuration requirements
     *
     * @throws GatewayException if configuration is invalid
     */
    protected function validateConfiguration(): void
    {
        if (empty($this->config)) {
            throw new GatewayException("Gateway configuration is empty for {$this->name}");
        }

        $requiredKeys = $this->getRequiredConfigKeys();
        foreach ($requiredKeys as $key) {
            if (empty($this->config[$key])) {
                throw new GatewayException("Missing required configuration: {$key}");
            }
        }
    }

    /**
     * Get required configuration keys for this gateway
     *
     * Override in child classes
     *
     * @return array List of required config keys
     */
    protected function getRequiredConfigKeys(): array
    {
        return [];
    }

    /**
     * Initialize the API client
     *
     * Override this method in child classes to initialize
     * their specific API client
     */
    protected function initializeClient(): void
    {
    }

    /**
     * Get a configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     *
     * @return mixed Configuration value
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Validate payment data structure
     *
     * @param array $data Payment data to validate
     *
     * @throws ValidationException if data is invalid
     */
    protected function validatePaymentData(array $data): void
    {
        $required = ['amount', 'currency'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new ValidationException("Missing required field: {$field}");
            }
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new ValidationException('Amount must be a positive number');
        }

        if (!is_string($data['currency']) || strlen($data['currency']) !== 3) {
            throw new ValidationException('Currency must be a 3-character code (ISO 4217)');
        }
    }

    /**
     * Create standardized error response
     */
    protected function formatErrorResponse(
        string $message,
        string $code = 'GATEWAY_ERROR',
        array $metadata = []
    ): array {
        return [
            'success' => false,
            'error' => $message,
            'error_code' => $code,
            'timestamp' => time(),
            'metadata' => $metadata,
        ];
    }

    /**
     * Create standardized success response
     */
    protected function formatSuccessResponse(
        array $data,
        string $status = 'completed'
    ): array {
        return array_merge([
            'success' => true,
            'status' => $status,
            'timestamp' => time(),
        ], $data);
    }
}
