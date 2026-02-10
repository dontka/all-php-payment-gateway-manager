# PayPal Payment Gateway

## Overview

PayPal integration for the Payment Gateway Manager supports the PayPal Orders API, allowing you to create orders, capture payments, handle refunds, and process webhooks.

**Features:**
- ✅ Create and manage orders
- ✅ Capture authorized payments
- ✅ Process refunds (full and partial)
- ✅ Verify payment status
- ✅ Handle IPN (Instant Payment Notification) webhooks
- ✅ Multi-currency support
- ✅ Sandbox and production environments

---

## Configuration

### Prerequisites

1. **PayPal Developer Account**
   - Sign up at https://developer.paypal.com
   - Create a new app in the Developer Dashboard
   - Obtain Client ID and Client Secret

2. **Environment Setup**
   ```bash
   PAYPAL_CLIENT_ID=your_client_id
   PAYPAL_CLIENT_SECRET=your_client_secret
   PAYPAL_MODE=sandbox  # or 'production'
   PAYPAL_WEBHOOK_ID=your_webhook_id  # optional
   ```

### Configuration File

Add to `config/payment.php`:

```php
'paypal' => [
    'class' => \PaymentGateway\Gateways\PayPalGateway::class,
    'client_id' => env('PAYPAL_CLIENT_ID'),
    'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    'mode' => env('PAYPAL_MODE', 'sandbox'),
    'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    'enabled' => true,
],
```

---

## Usage

### Basic Payment Flow

```php
use PaymentGateway\Core\PaymentManager;

$manager = new PaymentManager(config('payment'));

// Create payment order
$result = $manager->charge([
    'amount' => 99.99,
    'currency' => 'USD',
    'description' => 'Product purchase',
    'customer' => [
        'email' => 'customer@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ],
], 'paypal');

// Returns approval link for customer to authorize
if ($result['success']) {
    $approvalUrl = $result['approval_link'];
    // Redirect customer to: $approvalUrl
    $orderId = $result['order_id'];
    // Store orderId for later capture
}
```

### Capture Approved Order

```php
$manager = new PaymentManager(config('payment'));

$captureResult = $manager->gateway('paypal')->captureOrderPayment($orderId);

if ($captureResult['success']) {
    // Payment captured successfully
    $captureId = $captureResult['transaction_id'];
}
```

### Process Refund

```php
$refundResult = $manager->refund($captureId, 50.00, 'paypal', [
    'currency' => 'USD',
    'reason' => 'Customer request',
]);

if ($refundResult['success']) {
    // Refund processed
    $refundId = $refundResult['refund_id'];
}
```

### Verify Payment

```php
$status = $manager->verify($orderId, 'paypal');

// Returns:
// [
//     'status' => 'completed|pending|failed',
//     'amount' => 99.99,
//     'currency' => 'USD',
//     'timestamp' => 1234567890,
//     'metadata' => [ ... ]
// ]
```

---

## API Methods

### PayPalGateway::charge()

Initiates a payment by creating a PayPal order.

**Parameters:**
- `amount` (float): Payment amount
- `currency` (string): ISO 4217 currency code (e.g., 'USD', 'EUR')
- `description` (string): Order description
- `reference` (string): Your internal order reference
- `customer` (array): Customer information
  - `email` (string): Customer email
  - `first_name` (string): First name
  - `last_name` (string): Last name
  - `phone` (string): Phone number

**Response:**
```php
[
    'success' => true,
    'transaction_id' => 'PayPal order ID',
    'status' => 'pending',
    'amount' => 99.99,
    'currency' => 'USD',
    'order_id' => 'PayPal order ID',
    'approval_link' => 'https://www.sandbox.paypal.com/...',
    'metadata' => [ ... ]
]
```

### PayPalGateway::captureOrderPayment()

Captures an approved order.

**Parameters:**
- `orderId` (string): PayPal Order ID from charge response

**Response:**
```php
[
    'success' => true,
    'transaction_id' => 'Capture ID',
    'status' => 'completed',
    'amount' => 99.99,
    'currency' => 'USD',
    'metadata' => [ ... ]
]
```

### PayPalGateway::refund()

Issues a refund for a captured payment.

**Parameters:**
- `transactionId` (string): Capture ID to refund
- `amount` (float|null): Refund amount (null for full refund)
- `data` (array):
  - `currency` (string): Currency code
  - `reason` (string): Refund reason

**Response:**
```php
[
    'success' => true,
    'refund_id' => 'Refund ID',
    'status' => 'completed',
    'amount' => 50.00,
    'currency' => 'USD'
]
```

### PayPalGateway::verify()

Checks payment status.

**Parameters:**
- `transactionId` (string): Order ID

**Response:**
```php
[
    'status' => 'completed', // or 'pending', 'failed'
    'amount' => 99.99,
    'currency' => 'USD',
    'timestamp' => 1234567890,
    'metadata' => [ ... ]
]
```

---

## Webhooks

### Configuration

1. **Set Webhook URL in PayPal Dashboard**
   - Go to PayPal Developer Dashboard
   - Navigate to Apps & Credentials → Webhooks
   - Set webhook URL: `https://yourapp.com/webhooks/paypal`
   - Subscribe to events

2. **Supported Events**
   - `CHECKOUT.ORDER.APPROVED` - Order approved by customer
   - `PAYMENT-CAPTURE.COMPLETED` - Payment successfully captured
   - `PAYMENT-CAPTURE.DENIED` - Payment capture denied
   - `PAYMENT-CAPTURE.REFUNDED` - Payment refunded
   - `PAYMENT-CAPTURE.PENDING` - Payment pending

### Webhook Handler

```php
use PaymentGateway\Handlers\PayPalWebhookHandler;
use Illuminate\Http\Request;

public function handleWebhook(Request $request)
{
    $handler = new PayPalWebhookHandler();
    
    try {
        $result = $handler->handle(
            $request->json()->all(),
            $request->headers->all()
        );
        
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
```

---

## Supported Currencies

PayPal supports these currencies:

- USD, EUR, GBP, JPY, AUD, CAD, CHF, CNY, CZK, DKK, HKD, HUF, INR, ILS, MXN, MYR, NOK, NZD, PHP, PLN, RUB, SEK, SGD, THB, TRY, TWD

**Note:** Not all currencies are supported by all PayPal accounts. Verify currency support in your account settings.

---

## Multi-Currency Support

```php
// Payment in different currency
$result = $manager->charge([
    'amount' => 85.50,
    'currency' => 'EUR',  // Charge in Euros
    'customer' => [ ... ]
], 'paypal');

// Refund in same currency
$manager->refund($captureId, 40.00, 'paypal', [
    'currency' => 'EUR'
]);
```

---

## Webhook Handling Example

### Laravel Route

```php
// routes/api.php
Route::post('/webhooks/paypal', [WebhookController::class, 'paypal']);
```

### Controller

```php
use PaymentGateway\Handlers\PayPalWebhookHandler;

public function paypal(Request $request)
{
    $handler = new PayPalWebhookHandler();
    
    try {
        $result = $handler->handle(
            $request->json()->all(),
            $request->headers->all()
        );
        
        // Log successful processing
        Log::info('PayPal webhook processed', $result);
        
        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        Log::error('PayPal webhook error', [
            'error' => $e->getMessage(),
            'payload' => $request->json()->all()
        ]);
        
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
```

---

## Error Handling

### Common Errors

```php
try {
    $result = $manager->charge($paymentData, 'paypal');
} catch (GatewayException $e) {
    // Handle PayPal-specific errors
    switch ($e->getCode()) {
        case 'INVALID_REQUEST':
            // Invalid request data
            break;
        case 'AUTHENTICATION_FAILURE':
            // Invalid credentials
            break;
        case 'INSUFFICIENT_FUNDS':
            // Customer has insufficient funds
            break;
        default:
            // Other errors
    }
}
```

### Validation Errors

```php
use PaymentGateway\Exceptions\ValidationException;

try {
    $result = $manager->charge([
        'amount' => -10,  // Invalid
        'currency' => 'INVALID'
    ], 'paypal');
} catch (ValidationException $e) {
    echo $e->getMessage(); // "Amount must be positive"
}
```

---

## Testing

### Unit Tests

```bash
php vendor/bin/phpunit tests/Unit/Gateways/PayPalGatewayTest.php
```

### Integration Tests

```bash
# Requires sandbox credentials in .env
php vendor/bin/phpunit tests/Feature/PayPalIntegrationTest.php
```

### Testing Webhook Locally

Use ngrok to expose local webhook endpoint:

```bash
ngrok http 8000

# Set webhook URL in PayPal dashboard to:
# https://YOUR_NGROK_URL/webhooks/paypal
```

---

## Troubleshooting

### Common Issues

**Issue:** "Invalid credentials"
- **Solution:** Verify CLIENT_ID and CLIENT_SECRET are correct for chosen mode (sandbox vs production)

**Issue:** "Order not found"
- **Solution:** Order IDs expire after 3 hours. Create new order or use existing order within timeframe.

**Issue:** "Currency not supported"
- **Solution:** Verify currency is supported by your PayPal account. Check supported currencies list above.

**Issue:** Webhook not received
- **Solution:** 
  1. Verify webhook URL is publicly accessible
  2. Check webhook is enabled in PayPal Developer Dashboard
  3. Review webhook logs in PayPal Dashboard
  4. Ensure correct event types are subscribed

---

## Performance

- **Typical latency:** 200-500ms
- **Sandbox API:** Generally faster than production
- **Rate limits:** 50,000 API calls per hour

---

## Security

### Best Practices

1. **Never log or store credentials**
   ```php
   // ❌ WRONG
   Log::info('PayPal key: ' . $secretKey);
   
   // ✅ CORRECT
   Log::info('PayPal gateway initialized');
   ```

2. **Validate webhook signatures**
   ```php
   // PayPalWebhookHandler automatically verifies signatures
   $handler->handle($payload, $headers);
   ```

3. **Use HTTPS for webhooks**
   - Always use HTTPS endpoint for webhook URL

4. **Store credentials in .env**
   ```bash
   # .env
   PAYPAL_CLIENT_ID=xxx
   PAYPAL_CLIENT_SECRET=xxx
   ```

---

## API Reference

- PayPal Orders API: https://developer.paypal.com/docs/api/orders/v2/
- PayPal Payments: https://developer.paypal.com/docs/checkout/reference/
- Webhook Events: https://developer.paypal.com/docs/api-basics/notifications/webhooks/event-names/

---

## Support

For issues or questions:
- PayPal Developer Support: https://developer.paypal.com/support/
- Create issue on GitHub: https://github.com/dontka/all-php-payment-gateway-manager/issues
