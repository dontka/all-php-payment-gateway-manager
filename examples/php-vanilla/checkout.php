<?php

/**
 * PHP Vanilla Checkout Example
 *
 * Usage:
 *   Access via web: http://localhost/examples/php-vanilla/checkout.php
 *   Then fill the form and submit to test payment integration
 */

// Load configuration and dependencies
require_once __DIR__ . '/../../vendor/autoload.php';
$config = require_once __DIR__ . '/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Gateway Example</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .form-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section-title { font-size: 1.1rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 1rem; color: #333; }
        .example-box { background: #f0f7ff; padding: 1rem; border-left: 4px solid #0066cc; margin: 1rem 0; }
        code { color: #d63384; background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="form-container">
                    <h1>💳 Payment Gateway Integration Example</h1>
                    <p class="text-muted">PHP Vanilla Example - Learn how to integrate payment gateways</p>
                    
                    <!-- Payment Form -->
                    <div class="section-title">Step 1: Fill in Payment Details</div>
                    <form method="POST" action="/process-payment.php" id="paymentForm">
                        <div class="mb-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" placeholder="99.99" step="0.01" required>
                            <small class="text-muted">Enter the amount to charge</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Currency <span class="text-danger">*</span></label>
                            <select class="form-control" name="currency" required>
                                <option value="">Select currency...</option>
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                                <option value="JPY">JPY - Japanese Yen</option>
                                <option value="CAD">CAD - Canadian Dollar</option>
                                <option value="AUD">AUD - Australian Dollar</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" placeholder="customer@example.com" required>
                            <small class="text-muted">Customer email for payment receipt</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" placeholder="John Doe">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Gateway <span class="text-danger">*</span></label>
                            <select class="form-control" name="gateway" required>
                                <option value="">Select gateway...</option>
                                <option value="paypal">PayPal</option>
                                <option value="stripe">Stripe</option>
                                <option value="square">Square</option>
                            </select>
                            <small class="text-muted">Choose which payment provider to use</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description (optional)</label>
                            <input type="text" class="form-control" name="description" placeholder="Order #12345">
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">Proceed to Payment</button>
                    </form>

                    <!-- Implementation Guide -->
                    <div class="section-title">Step 2: Backend Implementation</div>
                    <div class="example-box">
                        <strong>PHP Backend Code:</strong>
                        <pre><code>// process-payment.php
use PaymentGateway\Core\PaymentManager;
use PaymentGateway\Gateways\PayPalGateway;

$manager = new PaymentManager();

$gateway = new PayPalGateway([
    'client_id' => $_ENV['PAYPAL_CLIENT_ID'],
    'client_secret' => $_ENV['PAYPAL_CLIENT_SECRET'],
    'mode' => 'sandbox'
]);

$manager->registerGateway('paypal', $gateway);

$result = $manager->gateway($_POST['gateway'])
    ->charge([
        'amount' => $_POST['amount'],
        'currency' => $_POST['currency'],
        'customer' => [
            'email' => $_POST['email'],
            'name' => $_POST['name']
        ]
    ]);</code></pre>
                    </div>

                    <!-- Configuration Guide -->
                    <div class="section-title">Step 3: Configuration</div>
                    <div class="alert alert-info">
                        <strong>📝 Environment Setup:</strong>
                        <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                            <li>Set <code>PAYPAL_CLIENT_ID</code> environment variable</li>
                            <li>Set <code>PAYPAL_CLIENT_SECRET</code> environment variable</li>
                            <li>Set <code>STRIPE_API_KEY</code> for Stripe support</li>
                            <li>Set <code>PAYPAL_MODE</code> to <code>sandbox</code> or <code>live</code></li>
                        </ul>
                    </div>

                    <!-- Usage Tips -->
                    <div class="section-title">Step 4: Next Steps</div>
                    <div class="alert alert-success">
                        <strong>✅ What this form demonstrates:</strong>
                        <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                            <li>Flexible payment amount and currency selection</li>
                            <li>Multi-gateway support (PayPal, Stripe, Square)</li>
                            <li>Customer data capture (email, name)</li>
                            <li>Simple HTML form for payment initiation</li>
                        </ul>
                        <strong style="display: block; margin-top: 1rem;">📚 Learn more:</strong>
                        <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                            <li>See <code>docs/INTEGRATION_GUIDE.md</code> for detailed setup</li>
                            <li>Check <code>examples/laravel/</code> for Laravel integration</li>
                            <li>Check <code>examples/symfony/</code> for Symfony integration</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple form validation feedback
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const amount = this.querySelector('[name="amount"]').value;
            const currency = this.querySelector('[name="currency"]').value;
            const email = this.querySelector('[name="email"]').value;
            const gateway = this.querySelector('[name="gateway"]').value;
            
            if (!amount || !currency || !email || !gateway) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
            
            alert(`Processing ${amount} ${currency} payment via ${gateway} for ${email}`);
        });
    </script>
</body>
</html>
