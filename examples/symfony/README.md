# Symfony Example - Payment Gateway Integration

This example demonstrates how to use the Payment Gateway package in a Symfony application.

## Installation

### Step 1: Install Package

```bash
composer require your-vendor/payment-gateway
```

### Step 2: Create Configuration File

Create `config/packages/payment_gateway.yaml`:

```yaml
payment_gateway:
  paypal:
    mode: '%env(PAYPAL_MODE)%'
    client_id: '%env(PAYPAL_CLIENT_ID)%'
    client_secret: '%env(PAYPAL_CLIENT_SECRET)%'
  stripe:
    secret_key: '%env(STRIPE_SECRET_KEY)%'
    public_key: '%env(STRIPE_PUBLIC_KEY)%'
```

## Configuration

### Using .env File

1. Copy credentials from [PayPal Developer Dashboard](https://developer.paypal.com)
2. Add to `.env`:

```env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_sandbox_client_id
PAYPAL_CLIENT_SECRET=your_sandbox_client_secret
STRIPE_PUBLIC_KEY=your_stripe_test_key
STRIPE_SECRET_KEY=your_stripe_secret_key
```

### Database Setup

Create migration for payments table:

```bash
php bin/console make:migration CreatePaymentsTable
```

Then execute:

```bash
php bin/console doctrine:migrations:migrate
```

## Usage

### In Controllers

```php
namespace App\Controller;

use PaymentGateway\PaymentManager;
use PaymentGateway\Models\Payment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class PaymentController extends AbstractController
{
    public function checkout(PaymentManager $paymentManager): Response
    {
        // Process payment
        $payment = $paymentManager->gateway('paypal')->charge([
            'amount' => 99.99,
            'currency' => 'USD',
            'customer_email' => 'customer@example.com',
            'description' => 'Product Purchase'
        ]);

        return $this->json([
            'transaction_id' => $payment->transaction_id,
            'status' => $payment->status
        ]);
    }
}
```

## Example: Full Payment Flow

### Controller

```php
namespace App\Controller;

use PaymentGateway\PaymentManager;
use PaymentGateway\Models\Payment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    private PaymentManager $paymentManager;
    private EntityManagerInterface $em;

    public function __construct(PaymentManager $paymentManager, EntityManagerInterface $em)
    {
        $this->paymentManager = $paymentManager;
        $this->em = $em;
    }

    #[Route('/', name: 'payment_show', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('payment/form.html.twig');
    }

    #[Route('/', name: 'payment_store', methods: ['POST'])]
    public function store(Request $request): Response
    {
        try {
            // Validate input
            $amount = $request->request->get('amount');
            $currency = $request->request->get('currency');
            $email = $request->request->get('email');
            $gateway = $request->request->get('gateway');

            if (!is_numeric($amount) || $amount < 0.01) {
                throw new \Exception('Invalid amount');
            }

            // Process payment
            $payment = $this->paymentManager
                ->gateway($gateway)
                ->charge([
                    'amount' => (float)$amount,
                    'currency' => $currency,
                    'customer_email' => $email,
                    'description' => 'Order Payment'
                ]);

            // Optionally save to database
            // $this->em->persist($payment);
            // $this->em->flush();

            return $this->redirectToRoute('payment_success', [
                'id' => $payment->transaction_id
            ]);

        } catch (\Exception $e) {
            return $this->render('payment/form.html.twig', [
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route('/success/{id}', name: 'payment_success')]
    public function success(string $id): Response
    {
        // Lookup payment from database or API
        return $this->render('payment/success.html.twig', [
            'transaction_id' => $id
        ]);
    }

    #[Route('/webhook/paypal', name: 'webhook_paypal', methods: ['POST'])]
    public function webhookPaypal(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $this->paymentManager->gateway('paypal')->handleWebhook($data);
            
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

### Twig Template

```twig
{# templates/payment/form.html.twig #}
<div class="card">
    <div class="card-header">Payment Form</div>
    <div class="card-body">
        {% if error %}
            <div class="alert alert-danger">
                {{ error }}
            </div>
        {% endif %}

        <form action="{{ path('payment_store') }}" method="POST">
            <div class="form-group mb-3">
                <label for="amount">Amount</label>
                <input type="number" step="0.01" class="form-control" 
                    name="amount" id="amount" required>
            </div>

            <div class="form-group mb-3">
                <label for="currency">Currency</label>
                <select class="form-control" name="currency" id="currency" required>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="GBP">GBP</option>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="email">Email</label>
                <input type="email" class="form-control" 
                    name="email" id="email" required>
            </div>

            <div class="form-group mb-3">
                <label for="gateway">Payment Gateway</label>
                <select class="form-control" name="gateway" id="gateway" required>
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

### Routes Configuration

```yaml
# config/routes.yaml
payment:
  resource: App\Controller\PaymentController
  type: attribute
```

## Service Container Injection

Register Payment Manager as service in `config/services.yaml`:

```yaml
services:
  PaymentGateway\PaymentManager:
    arguments:
      - '@logger'
      - '@doctrine.orm.entity_manager'
```

## Event Listeners

Create an event listener for payment success:

```php
// src/EventListener/PaymentSuccessListener.php
namespace App\EventListener;

use PaymentGateway\Events\PaymentSuccessEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: PaymentSuccessEvent::class)]
class PaymentSuccessListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onPaymentSuccess(PaymentSuccessEvent $event): void
    {
        $payment = $event->getPayment();
        
        $this->logger->info('Payment successful', [
            'transaction_id' => $payment->transaction_id,
            'amount' => $payment->amount,
            'email' => $payment->customer_email
        ]);

        // Send email, create order, etc.
    }
}
```

## Testing

### Unit Test

```php
// tests/PaymentControllerTest.php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    public function testPaymentFormDisplay(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/payment/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Payment Form');
    }

    public function testPaymentProcessing(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/payment/', [
            'amount' => 99.99,
            'currency' => 'USD',
            'email' => 'test@example.com',
            'gateway' => 'paypal'
        ]);

        $this->assertResponseRedirects('/payment/success');
    }
}
```

## Troubleshooting

### PayPal Integration Issues

1. **Invalid Credentials**
   - Verify `.env` has correct credentials
   - Check PAYPAL_MODE is `sandbox` for testing
   - Get new credentials from [PayPal Dashboard](https://developer.paypal.com)

2. **Webhook Not Received**
   - Verify webhook URL in PayPal account settings
   - Check Symfony logs: `tail -f var/log/dev.log`
   - Ensure route is accessible from internet

3. **Database Errors**
   - Run migrations: `php bin/console doctrine:migrations:migrate`
   - Check database credentials in `.env`

## CLI Commands

See payment history:

```bash
php bin/console doctrine:query:sql "SELECT * FROM payments"
```

Clear cache:

```bash
php bin/console cache:clear
```

## Next Steps

1. Add payment history page
2. Implement refund functionality
3. Add email notifications
4. Setup webhook receiver
5. Add multiple currency support

See [Integration Guide](../../docs/INTEGRATION_GUIDE.md) for more.

## Resources

- 📖 [Package Documentation](../../docs/)
- 🔑 [PayPal Developer Dashboard](https://developer.paypal.com)
- 💳 [Stripe Documentation](https://stripe.com/docs)
- 🚀 [Symfony Documentation](https://symfony.com/doc/current)
