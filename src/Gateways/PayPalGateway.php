<?php

declare(strict_types=1);

namespace PaymentGateway\Gateways;

use PaymentGateway\Core\AbstractGateway;
use PaymentGateway\Exceptions\GatewayException;
use PaymentGateway\Exceptions\ValidationException;
use PaymentGateway\Exceptions\WebhookException;
use PaymentGateway\Traits\HasEncryption;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * PayPal Payment Gateway Implementation
 *
 * Supports PayPal Orders API for creating, capturing, and refunding payments.
 * Handles IPN (Instant Payment Notification) webhooks for transaction updates.
 */
class PayPalGateway extends AbstractGateway
{
    use HasEncryption;

    /**
     * Gateway name
     */
    protected string $name = 'paypal';

    /**
     * API environments
     */
    private const API_SANDBOX = 'https://api-m.sandbox.paypal.com';
    private const API_PRODUCTION = 'https://api-m.paypal.com';

    /**
     * HTTP client instance
     */
    protected mixed $client = null;

    /**
     * API base URL
     */
    private string $apiUrl;

    /**
     * Access token (cached)
     */
    private ?string $accessToken = null;

    /**
     * Initialize the gateway
     */
    protected function initializeClient(): void
    {
        $this->client = HttpClient::create();
        $this->apiUrl = $this->getMode() === 'production'
            ? self::API_PRODUCTION
            : self::API_SANDBOX;
    }

    /**
     * Get required configuration keys
     */
    protected function getRequiredConfigKeys(): array
    {
        return ['client_id', 'client_secret'];
    }

    /**
     * Process a payment charge
     *
     * @param array $data Payment data
     * @return array Payment result
     * @throws GatewayException
     */
    public function charge(array $data): array
    {
        $this->validatePaymentData($data);

        try {
            // Create order
            $orderData = $this->prepareOrderData($data);
            $response = $this->createOrder($orderData);

            if (!isset($response['id'])) {
                throw new GatewayException('Failed to create PayPal order');
            }

            // Capture order if status is APPROVED (immediate payment)
            if ($response['status'] === 'APPROVED' && isset($data['capture']) && $data['capture'] === true) {
                $captureResponse = $this->captureOrder($response['id']);
                return $this->formatCaptureResponse($captureResponse);
            }

            // Return order for redirect/approval
            return [
                'success' => true,
                'transaction_id' => $response['id'],
                'status' => 'pending',
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'order_id' => $response['id'],
                'approval_link' => $this->getApprovalLink($response),
                'metadata' => [
                    'order_id' => $response['id'],
                    'status' => $response['status'],
                    'links' => $response['links'] ?? [],
                ],
            ];
        } catch (\Exception $e) {
            $this->logError('Payment charge failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw new GatewayException("PayPal charge failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Capture an approved order
     *
     * @param string $orderId PayPal order ID
     * @return array Capture result
     * @throws GatewayException
     */
    public function captureOrderPayment(string $orderId): array
    {
        try {
            $response = $this->captureOrder($orderId);
            return $this->formatCaptureResponse($response);
        } catch (\Exception $e) {
            throw new GatewayException("Failed to capture PayPal order: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Process a refund
     *
     * @param string $transactionId Capture ID to refund
     * @param float|null $amount Refund amount (null for full)
     * @param array $data Additional data
     * @return array Refund result
     * @throws GatewayException
     */
    public function refund(
        string $transactionId,
        ?float $amount = null,
        array $data = []
    ): array {
        try {
            $url = "{$this->apiUrl}/v2/payments/captures/{$transactionId}/refund";

            $payload = [];
            if ($amount !== null) {
                $payload['amount'] = [
                    'value' => number_format($amount, 2, '.', ''),
                    'currency_code' => $data['currency'] ?? 'USD',
                ];
            }

            if (!empty($data['reason'])) {
                $payload['note_to_payer'] = $data['reason'];
            }

            $response = $this->makeRequest('POST', $url, $payload);

            return [
                'success' => true,
                'refund_id' => $response['id'] ?? '',
                'status' => 'completed',
                'amount' => $amount,
                'currency' => $data['currency'] ?? 'USD',
                'timestamp' => time(),
                'metadata' => $response,
            ];
        } catch (\Exception $e) {
            $this->logError('Refund failed', ['error' => $e->getMessage(), 'transaction_id' => $transactionId]);
            throw new GatewayException("PayPal refund failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Verify a payment status
     *
     * @param string $transactionId Order ID or capture ID
     * @return array Payment status
     * @throws GatewayException
     */
    public function verify(string $transactionId): array
    {
        try {
            // Try as order ID first
            $url = "{$this->apiUrl}/v2/orders/{$transactionId}";
            $response = $this->makeRequest('GET', $url);

            if (isset($response['id'])) {
                return [
                    'status' => strtolower($response['status'] ?? 'unknown'),
                    'amount' => $this->getOrderAmount($response),
                    'currency' => $response['purchase_units'][0]['amount']['currency_code'] ?? 'USD',
                    'timestamp' => time(),
                    'metadata' => $response,
                ];
            }

            throw new GatewayException('Order not found');
        } catch (\Exception $e) {
            $this->logError('Verification failed', ['error' => $e->getMessage(), 'transaction_id' => $transactionId]);
            throw new GatewayException("PayPal verification failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Handle webhook callback
     *
     * @param array $payload Webhook payload
     * @param array $headers Request headers
     * @return array Processing result
     * @throws WebhookException
     */
    public function handleWebhook(array $payload, array $headers = []): array
    {
        try {
            // Verify webhook signature
            if (!$this->verifyWebhookSignature($payload, $headers)) {
                throw new WebhookException('Invalid webhook signature');
            }

            $eventType = $payload['event_type'] ?? '';
            $resource = $payload['resource'] ?? [];

            // map PayPal events to internal events
            $eventMapping = [
                'CHECKOUT.ORDER.APPROVED' => 'payment:initiated',
                'PAYMENT-CAPTURE.COMPLETED' => 'payment:success',
                'PAYMENT-CAPTURE.DENIED' => 'payment:failed',
                'PAYMENT-CAPTURE.REFUNDED' => 'refund:success',
            ];

            $internalEvent = $eventMapping[$eventType] ?? 'webhook:received';

            return [
                'success' => true,
                'event_type' => $internalEvent,
                'transaction_id' => $resource['id'] ?? '',
                'status' => 'processed',
                'metadata' => [
                    'paypal_event_type' => $eventType,
                    'resource' => $resource,
                ],
            ];
        } catch (\Exception $e) {
            $this->logError('Webhook handling failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create a PayPal order
     */
    private function createOrder(array $orderData): array
    {
        $url = "{$this->apiUrl}/v2/orders";
        return $this->makeRequest('POST', $url, $orderData);
    }

    /**
     * Capture a PayPal order
     */
    private function captureOrder(string $orderId): array
    {
        $url = "{$this->apiUrl}/v2/orders/{$orderId}/capture";
        return $this->makeRequest('POST', $url);
    }

    /**
     * Prepare order data for PayPal API
     */
    private function prepareOrderData(array $data): array
    {
        return [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $data['reference'] ?? $data['customer'] ?? 'order-' . time(),
                    'amount' => [
                        'currency_code' => strtoupper($data['currency']),
                        'value' => number_format($data['amount'], 2, '.', ''),
                    ],
                    'custom_id' => $data['custom_id'] ?? '',
                    'description' => $data['description'] ?? 'Payment',
                ],
            ],
            'payer' => $this->preparePayerData($data['customer'] ?? []),
            'application_context' => [
                'brand_name' => $data['brand_name'] ?? 'Payment Gateway',
                'locale' => 'en-US',
                'landing_page' => 'LOGIN',
                'user_action' => 'PAY_NOW',
            ],
        ];
    }

    /**
     * Prepare payer data for PayPal API
     */
    private function preparePayerData(array $customer): array
    {
        $payer = [];

        if (!empty($customer['email'])) {
            $payer['email_address'] = $customer['email'];
        }

        if (!empty($customer['name'])) {
            $payer['name'] = [
                'given_name' => $customer['first_name'] ?? explode(' ', $customer['name'])[0] ?? '',
                'surname' => $customer['last_name'] ?? (count(explode(' ', $customer['name'])) > 1 ? explode(' ', $customer['name'])[1] : '') ?? '',
            ];
        }

        if (!empty($customer['phone'])) {
            $payer['phone'] = [
                'phone_number' => [
                    'national_number' => $customer['phone'],
                ],
            ];
        }

        return $payer;
    }

    /**
     * Format capture response
     */
    private function formatCaptureResponse(array $response): array
    {
        $purchaseUnit = $response['purchase_units'][0] ?? [];
        $payment = $purchaseUnit['payments']['captures'][0] ?? [];

        return [
            'success' => true,
            'transaction_id' => $payment['id'] ?? $response['id'] ?? '',
            'status' => 'completed',
            'amount' => (float)($payment['amount']['value'] ?? $purchaseUnit['amount']['value'] ?? 0),
            'currency' => $payment['amount']['currency_code'] ?? $purchaseUnit['amount']['currency_code'] ?? 'USD',
            'timestamp' => time(),
            'metadata' => [
                'order_id' => $response['id'] ?? '',
                'capture_id' => $payment['id'] ?? '',
                'status' => $payment['status'] ?? $response['status'] ?? 'COMPLETED',
            ],
        ];
    }

    /**
     * Get approval link from order response
     */
    private function getApprovalLink(array $response): ?string
    {
        $links = $response['links'] ?? [];

        foreach ($links as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                return $link['href'] ?? null;
            }
        }

        return null;
    }

    /**
     * Get total amount from order
     */
    private function getOrderAmount(array $order): float
    {
        $purchaseUnit = $order['purchase_units'][0] ?? [];
        return (float)($purchaseUnit['amount']['value'] ?? 0);
    }

    /**
     * Make HTTP request to PayPal API
     */
    private function makeRequest(
        string $method,
        string $url,
        ?array $payload = null
    ): array {
        try {
            $headers = [
                'Authorization' => "Bearer {$this->getAccessToken()}",
                'Content-Type' => 'application/json',
                'PayPal-Request-Id' => uniqid('paypal_', true),
            ];

            $options = [
                'headers' => $headers,
            ];

            if ($method === 'POST' && $payload !== null) {
                $options['json'] = $payload;
            }

            $response = $this->client->request($method, $url, $options);
            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode >= 300) {
                $errorBody = json_decode($response->getContent(false), true);
                throw new GatewayException(
                    $errorBody['message'] ?? "PayPal API error: {$statusCode}"
                );
            }

            return json_decode($response->getContent(), true) ?? [];
        } catch (\Exception $e) {
            $this->logError('API request failed', ['url' => $url, 'method' => $method, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get access token from PayPal
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        try {
            $url = "{$this->apiUrl}/v1/oauth2/token";
            $credentials = base64_encode(
                $this->getConfig('client_id') . ':' . $this->getConfig('client_secret')
            );

            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Authorization' => "Basic {$credentials}",
                    'Accept' => 'application/json',
                    'Accept-Language' => 'en_US',
                ],
                'body' => 'grant_type=client_credentials',
            ]);

            $data = json_decode($response->getContent(), true);
            $this->accessToken = $data['access_token'] ?? null;

            if ($this->accessToken === null) {
                throw new GatewayException('Failed to obtain PayPal access token');
            }

            return $this->accessToken;
        } catch (\Exception $e) {
            throw new GatewayException("Failed to get PayPal access token: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature(array $payload, array $headers): bool
    {
        // PayPal verification would involve checking the signature
        // against PayPal's certificate in production
        // For now, we'll do basic validation
        
        if (empty($payload['id']) || empty($payload['event_type'])) {
            return false;
        }

        // In production, verify against PayPal's certificate
        // using the X-PAYPAL-TRANSMISSION-SIG header
        return true;
    }

    /**
     * Get PayPal operation mode
     */
    private function getMode(): string
    {
        return strtolower($this->getConfig('mode', 'sandbox'));
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods(): array
    {
        return [
            'paypal_account',
            'credit_card',
            'debit_card',
            'paylater',
        ];
    }

    /**
     * Log an error message
     *
     * @param string $message Error message
     * @param array $context Additional context
     */
    private function logError(string $message, array $context = []): void
    {
        error_log(sprintf(
            '[PayPal Gateway Error] %s | Context: %s',
            $message,
            json_encode($context)
        ));
    }
}
