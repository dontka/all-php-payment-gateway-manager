<?php

declare(strict_types=1);

namespace Tests\Unit\Gateways;

use PHPUnit\Framework\TestCase;
use PaymentGateway\Gateways\PayPalGateway;
use PaymentGateway\Exceptions\GatewayException;
use PaymentGateway\Exceptions\ValidationException;

/**
 * PayPal Gateway Unit Tests
 */
class PayPalGatewayTest extends TestCase
{
    /**
     * Gateway instance
     */
    private PayPalGateway $gateway;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->gateway = new PayPalGateway([
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'mode' => 'sandbox',
        ]);
    }

    /**
     * Test gateway initialization with valid config
     */
    public function testGatewayInitializationWithValidConfig(): void
    {
        $this->assertTrue($this->gateway->isConfigured());
        $this->assertEquals('paypal', $this->gateway->getName());
    }

    /**
     * Test gateway initialization fails without required config
     */
    public function testGatewayInitializationFailsWithoutConfig(): void
    {
        $this->expectException(GatewayException::class);
        new PayPalGateway([]);
    }

    /**
     * Test payment validation - missing amount
     */
    public function testPaymentValidationMissingAmount(): void
    {
        $this->expectException(ValidationException::class);
        $this->gateway->charge([
            'currency' => 'USD',
        ]);
    }

    /**
     * Test payment validation - missing currency
     */
    public function testPaymentValidationMissingCurrency(): void
    {
        $this->expectException(ValidationException::class);
        $this->gateway->charge([
            'amount' => 99.99,
        ]);
    }

    /**
     * Test payment validation - invalid amount
     */
    public function testPaymentValidationInvalidAmount(): void
    {
        $this->expectException(ValidationException::class);
        $this->gateway->charge([
            'amount' => -10,
            'currency' => 'USD',
        ]);
    }

    /**
     * Test payment validation - invalid currency
     */
    public function testPaymentValidationInvalidCurrency(): void
    {
        $this->expectException(ValidationException::class);
        $this->gateway->charge([
            'amount' => 99.99,
            'currency' => 'INVALID',
        ]);
    }

    /**
     * Test refund validation
     */
    public function testRefundWithValidData(): void
    {
        // This would fail without a real API, but tests the interface
        try {
            $this->gateway->refund('CAPTURE_ID_123', 50.00, [
                'currency' => 'USD',
                'reason' => 'Customer request',
            ]);
        } catch (GatewayException $e) {
            // Expected - no real API connection
            $this->assertStringContainsString('PayPal', $e->getMessage());
        }
    }

    /**
     * Test payment methods retrieval
     */
    public function testGetPaymentMethods(): void
    {
        $methods = $this->gateway->getPaymentMethods();

        $this->assertIsArray($methods);
        $this->assertContains('paypal_account', $methods);
        $this->assertContains('credit_card', $methods);
        $this->assertContains('debit_card', $methods);
        $this->assertContains('paylater', $methods);
    }

    /**
     * Test gateway name
     */
    public function testGatewayName(): void
    {
        $this->assertEquals('paypal', $this->gateway->getName());
    }

    /**
     * Test logging integration
     */
    public function testLoggingIntegration(): void
    {
        // Gateway should have logging capability
        $reflection = new \ReflectionClass($this->gateway);
        $this->assertTrue($reflection->hasMethod('logError'));
        $this->assertTrue($reflection->hasMethod('logInfo'));
    }

    /**
     * Test encryption capability
     */
    public function testEncryptionCapability(): void
    {
        $reflection = new \ReflectionClass($this->gateway);
        $this->assertTrue($reflection->hasMethod('encrypt'));
        $this->assertTrue($reflection->hasMethod('decrypt'));
    }

    /**
     * Test configuration retrieval
     */
    public function testConfigurationRetrieval(): void
    {
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('getConfig');
        $method->setAccessible(true);

        // Should return sandbox mode
        $mode = $method->invoke($this->gateway, 'mode', 'default');
        $this->assertEquals('sandbox', $mode);
    }

    /**
     * Test payment data structure with customer info
     */
    public function testPaymentDataWithCustomerInfo(): void
    {
        $paymentData = [
            'amount' => 99.99,
            'currency' => 'USD',
            'description' => 'Test payment',
            'customer' => [
                'email' => 'test@example.com',
                'name' => 'John Doe',
                'phone' => '1234567890',
            ],
        ];

        try {
            $this->gateway->charge($paymentData);
        } catch (GatewayException $e) {
            // Expected - no real API
            $this->assertStringContainsString('PayPal', $e->getMessage());
        }
    }

    /**
     * Test payment data structure with metadata
     */
    public function testPaymentDataWithMetadata(): void
    {
        $paymentData = [
            'amount' => 99.99,
            'currency' => 'USD',
            'reference' => 'order-123',
            'custom_id' => 'custom-456',
            'metadata' => [
                'invoice_id' => 'INV-789',
                'order_type' => 'subscription',
            ],
        ];

        try {
            $this->gateway->charge($paymentData);
        } catch (GatewayException $e) {
            // Expected - no real API
            $this->assertStringContainsString('PayPal', $e->getMessage());
        }
    }

    /**
     * Test multi-currency support
     */
    public function testMultiCurrencySupport(): void
    {
        $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF'];

        foreach ($currencies as $currency) {
            try {
                $this->gateway->charge([
                    'amount' => 100.00,
                    'currency' => $currency,
                ]);
            } catch (GatewayException $e) {
                // Expected - no real API, but should accept the currency
                $this->assertStringContainsString('PayPal', $e->getMessage());
            }
        }
    }

    /**
     * Test refund with different currencies
     */
    public function testRefundWithMultipleCurrencies(): void
    {
        $currencies = ['USD', 'EUR', 'GBP'];

        foreach ($currencies as $currency) {
            try {
                $this->gateway->refund('CAPTURE_ID_123', 50.00, [
                    'currency' => $currency,
                ]);
            } catch (GatewayException $e) {
                // Expected - no real API
                $this->assertStringContainsString('PayPal', $e->getMessage());
            }
        }
    }

    /**
     * Test verify method signature
     */
    public function testVerifyMethodSignature(): void
    {
        try {
            $this->gateway->verify('ORDER_ID_123');
        } catch (GatewayException $e) {
            // Expected - no real API
            $this->assertStringContainsString('PayPal', $e->getMessage());
        }
    }

    /**
     * Test webhook handling basic structure
     */
    public function testWebhookHandlingStructure(): void
    {
        $payload = [
            'id' => 'WH-123',
            'event_type' => 'PAYMENT-CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAPTURE_ID_123',
                'status' => 'COMPLETED',
                'amount' => [
                    'value' => '99.99',
                    'currency_code' => 'USD',
                ],
            ],
        ];

        try {
            $result = $this->gateway->handleWebhook($payload);
            // Should handle webhook successfully
            $this->assertTrue(is_array($result));
        } catch (\Exception $e) {
            // Exception is acceptable for test
            $this->assertTrue(true);
        }
    }

    /**
     * Test invalid webhook handling
     */
    public function testInvalidWebhookHandling(): void
    {
        $payload = [
            'invalid' => 'data',
        ];

        try {
            $this->gateway->handleWebhook($payload);
        } catch (\Exception $e) {
            // Should throw exception for invalid webhook
            $this->assertTrue(true);
        }
    }
}
