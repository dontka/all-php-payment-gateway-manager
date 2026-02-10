# Laravel Example - Payment Gateway Integration

This example demonstrates how to use the Payment Gateway package in a Laravel application.

## Installation

### Step 1: Install Package

```bash
composer require your-vendor/payment-gateway
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --provider="YourVendor\PaymentGateway\ServiceProvider"
```

This creates `config/payment-gateway.php` with sensible defaults.

## Configuration

### Using Laravel .env

1. Copy PayPal credentials from [PayPal Developer Dashboard](https://developer.paypal.com)
2. Add to your `.env` file:

```env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_sandbox_client_id
PAYPAL_CLIENT_SECRET=your_sandbox_client_secret
STRIPE_PUBLIC_KEY=your_stripe_test_key
STRIPE_SECRET_KEY=your_stripe_secret_key
```

### Database Setup

Run migrations to create payment tables:

```bash
php artisan migrate
```

This creates:
- `payments` table (stores all payment records)
- `webhook_logs` table (stores webhook events)

## Usage

### Method 1: Using Facade (Easiest)

```php
use PaymentGateway\Facades\PaymentManager;
use PaymentGateway\Models\Payment;

// Charge a payment
$payment = PaymentManager::gateway('paypal')->charge([
    'amount' => 99.99,
    'currency' => 'USD',
    'customer_email' => 'customer@example.com',
    'description' => 'Product Purchase'
]);

// $payment is a Payment model - you can save to DB or check status
echo $payment->transaction_id;
```

### Method 2: Using Service Container

```php
use PaymentGateway\PaymentManager;

public function processPayment(PaymentManager $paymentManager)
{
    $result = $paymentManager->gateway('paypal')->charge([
        'amount' => 99.99,
        'currency' => 'USD',
        'customer_email' => 'customer@example.com',
        'description' => 'Product Purchase'
    ]);
}
```

## Example: Full Payment Flow

### Controller

```php
namespace App\Http\Controllers;

use PaymentGateway\Facades\PaymentManager;
use PaymentGateway\Models\Payment;

class PaymentController extends Controller
{
    // Show payment form
    public function show()
    {
        return view('payment.form');
    }

    // Process payment
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,EUR,GBP,JPY',
            'email' => 'required|email',
            'gateway' => 'required|in:paypal,stripe'
        ]);

        try {
            // Process payment
            $payment = PaymentManager::gateway($validated['gateway'])
                ->charge([
                    'amount' => $validated['amount'],
                    'currency' => $validated['currency'],
                    'customer_email' => $validated['email'],
                    'description' => 'Order Payment'
                ]);

            // Save to database
            $payment->save();

            return redirect()->route('payment.success')
                ->with('payment', $payment);

        } catch (\Exception $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }

    // Show success page
    public function success(Request $request)
    {
        $payment = Payment::latest()->first();
        return view('payment.success', ['payment' => $payment]);
    }

    // Handle webhook
    public function webhook(Request $request)
    {
        $gateway = $request->input('gateway', 'paypal');
        
        PaymentManager::gateway($gateway)->handleWebhook(
            $request->all()
        );

        return response()->json(['success' => true]);
    }
}
```

### Routes

```php
// routes/web.php
Route::get('/payment', [PaymentController::class, 'show'])->name('payment.show');
Route::post('/payment', [PaymentController::class, 'store'])->name('payment.store');
Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');

// Webhook endpoint
Route::post('/webhooks/payment', [PaymentController::class, 'webhook'])->name('webhook.payment');
```

### Blade Template

```blade
<!-- resources/views/payment/form.blade.php -->
<div class="card">
    <div class="card-header">Payment</div>
    <div class="card-body">
        <form action="{{ route('payment.store') }}" method="POST">
            @csrf
            
            <div class="form-group mb-3">
                <label>Amount</label>
                <input type="number" step="0.01" name="amount" required>
            </div>

            <div class="form-group mb-3">
                <label>Currency</label>
                <select name="currency" required>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="GBP">GBP</option>
                </select>
            </div>

            <div class="form-group mb-3">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group mb-3">
                <label>Payment Gateway</label>
                <select name="gateway" required>
                    <option value="paypal">PayPal</option>
                    <option value="stripe">Stripe</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                Process Payment
            </button>
        </form>
    </div>
</div>
```

## Advanced Features

### Refund a Payment

```php
use PaymentGateway\Facades\PaymentManager;

$payment = Payment::find($id);

$refunded = PaymentManager::gateway($payment->gateway)
    ->refund($payment->transaction_id, [
        'amount' => 50.00  // Partial refund
    ]);
```

### Verify Payment Status

```php
$verified = PaymentManager::gateway('paypal')
    ->verify($transaction_id);

echo $verified['status']; // 'completed', 'pending', 'failed'
```

### Listen to Payment Events

```php
// app/Listeners/PaymentSucceededListener.php
namespace App\Listeners;

use PaymentGateway\Events\PaymentSuccessEvent;

class PaymentSucceededListener
{
    public function handle(PaymentSuccessEvent $event)
    {
        $payment = $event->payment;
        
        // Send confirmation email
        Mail::to($payment->customer_email)->send(new PaymentConfirmation($payment));
        
        // Fulfill order
        Order::find($payment->order_id)->markAsFulfilled();
    }
}
```

Register in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    PaymentSuccessEvent::class => [
        PaymentSucceededListener::class,
    ],
];
```

## Testing Payments

### Test Cards

PayPal Sandbox provides test accounts. Use these:

**Successful Payment:**
- Email: sb-xxxxx@personal.example.com
- Password: 123456

**Failed Payment:**
- Try any amount (will simulate failure)

### Unit Testing

```php
// tests/Feature/PaymentTest.php
public function test_can_process_paypal_payment()
{
    $response = $this->post(route('payment.store'), [
        'amount' => 99.99,
        'currency' => 'USD',
        'email' => 'test@example.com',
        'gateway' => 'paypal'
    ]);

    $this->assertDatabaseHas('payments', [
        'amount' => 99.99,
        'gateway' => 'paypal',
        'status' => 'pending'
    ]);
}
```

## Troubleshooting

### Payment fails with "Invalid credentials"

1. Verify `.env` has correct PayPal credentials
2. Check PAYPAL_MODE is set to `sandbox` for testing
3. Get fresh credentials from [PayPal Dashboard](https://developer.paypal.com)

### Webhook not received

1. Configure webhook URL in gateway settings
2. Check logs in `storage/logs/laravel.log`
3. Verify webhook handler is registered in routes

### Database errors

1. Run migrations: `php artisan migrate`
2. Check database connection in `.env`
3. Verify tables exist: `php artisan make:migration create_payments_table`

## Next Steps

1. Create payment history page
2. Add email notifications
3. Implement webhook handling
4. Add refund management
5. Enable multiple gateways

See [Integration Guide](../../docs/INTEGRATION_GUIDE.md) for more examples.

## Resources

- 📖 [Package Documentation](../../docs/)
- 🔑 [PayPal Developer Dashboard](https://developer.paypal.com)
- 💳 [Stripe Documentation](https://stripe.com/docs)
- 🚀 [Laravel Documentation](https://laravel.com/docs)
