<?php

/**
 * PHP Vanilla Checkout Example
 *
 * Usage:
 *   Access via web: http://localhost/examples/php-vanilla/checkout.php
 *   Fill the form and submit to process payment with the package
 */

// Load configuration and dependencies
require_once __DIR__ . '/../../vendor/autoload.php';
$config = require_once __DIR__ . '/config.php';

use PaymentGateway\Core\PaymentManager;
use PaymentGateway\Gateways\PayPalGateway;

// Initialize Payment Manager
$paymentManager = new PaymentManager();
$paymentResult = null;
$paymentError = null;

// Configure gateways
try {
    $paypalGateway = new PayPalGateway([
        'client_id' => $config['paypal']['client_id'],
        'client_secret' => $config['paypal']['client_secret'],
        'mode' => $config['paypal']['mode']
    ]);
    $paymentManager->registerGateway('paypal', $paypalGateway);

    // Register Stripe gateway if available (coming in Week 2.1)
    if (class_exists('PaymentGateway\Gateways\StripeGateway') && !empty($config['stripe']['api_key'])) {
        $stripeGateway = new \PaymentGateway\Gateways\StripeGateway([
            'api_key' => $config['stripe']['api_key'],
            'secret_key' => $config['stripe']['secret_key']
        ]);
        $paymentManager->registerGateway('stripe', $stripeGateway);
    }
} catch (\Exception $e) {
    $paymentError = "Gateway initialization failed: " . $e->getMessage();
    
    // Check if using placeholder credentials
    if (strpos($config['paypal']['client_id'], 'YOUR_') !== false) {
        $paymentError .= "<br><strong>⚠️ Setup Required:</strong> Please configure your PayPal sandbox credentials in the <code>.env</code> file. "
            . "Get credentials from <a href='https://developer.paypal.com' target='_blank'>PayPal Developer Dashboard</a>";
    }
}

// Handle form submission
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !$paymentError) {
    try {
        $gateway = $_POST['gateway'] ?? 'paypal';
        
        // Validate gateway is available
        if ($gateway === 'stripe' && !class_exists('PaymentGateway\Gateways\StripeGateway')) {
            throw new Exception('Stripe gateway is not yet available. Please use PayPal.');
        }
        
        $paymentResult = $paymentManager->gateway($gateway)->charge([
            'amount' => (float) $_POST['amount'],
            'currency' => $_POST['currency'],
            'customer' => [
                'email' => $_POST['email'],
                'name' => $_POST['name'] ?? 'Customer'
            ],
            'description' => $_POST['description'] ?? 'Payment'
        ]);
        
    } catch (\Exception $e) {
        $paymentError = "Payment processing failed: " . $e->getMessage();
    }
}

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
                    <p class="text-muted">PHP Vanilla Example - Direct integration with the payment package</p>
                    
                    <!-- Debug Link -->
                    <div class="alert alert-info" role="alert" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>🔍 Having trouble?</strong> Check your configuration with the debug tool
                        </div>
                        <a href="debug.php" class="btn btn-sm btn-info">Open Debug Tool</a>
                    </div>
                    
                    <!-- Payment Result Messages -->
                    <?php if ($paymentError): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading">❌ Error</h5>
                            <p><?php echo $paymentError; ?></p>
                            
                            <!-- Troubleshooting Section -->
                            <?php if (strpos($paymentError, 'credentials') !== false || strpos($paymentError, 'authentication') !== false || strpos($paymentError, '404') !== false): ?>
                                <hr>
                                <strong>💡 How to Troubleshoot:</strong>
                                <ol style="margin: 0.5rem 0; padding-left: 1.5rem;">
                                    <li><a href="debug.php" class="btn btn-sm btn-outline-danger">Run Configuration Debug</a> to verify your credentials are being loaded</li>
                                    <li>Make sure your <code>.env</code> file is in the <strong>project root</strong> (not in examples/php-vanilla/)</li>
                                    <li>Verify your PayPal credentials:
                                        <ul style="margin: 0.5rem 0; padding-left: 1rem;">
                                            <li>Go to <a href="https://developer.paypal.com/dashboard/" target="_blank">PayPal Developer Dashboard</a></li>
                                            <li>Click "Apps & Credentials" (top menu)</li>
                                            <li>Make sure "Sandbox" is selected (top right)</li>
                                            <li>Find your app and verify the Client ID matches your .env file</li>
                                            <li>If different, copy the correct credentials and update your .env</li>
                                        </ul>
                                    </li>
                                    <li>If credentials look correct, they might be <strong>expired or revoked</strong>:
                                        <ul style="margin: 0.5rem 0; padding-left: 1rem;">
                                            <li>Generate new credentials in PayPal Dashboard</li>
                                            <li>Update your .env file with the new values</li>
                                            <li>Try again</li>
                                        </ul>
                                    </li>
                                    <li>Reload this page and try the payment form again</li>
                                </ol>
                            <?php endif; ?>
                            
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($paymentResult && $paymentResult['success']): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading">✅ Payment Created Successfully!</h5>
                            <p><strong>Gateway:</strong> <?php echo htmlspecialchars($_POST['gateway']); ?></p>
                            <p><strong>Amount:</strong> <?php echo htmlspecialchars($_POST['amount']); ?> <?php echo htmlspecialchars($_POST['currency']); ?></p>
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($_POST['email']); ?></p>
                            <?php if (!empty($paymentResult['transaction_id'])): ?>
                                <p><strong>Transaction ID:</strong> <code><?php echo htmlspecialchars($paymentResult['transaction_id']); ?></code></p>
                            <?php endif; ?>
                            <?php if (!empty($paymentResult['order_id'])): ?>
                                <p><strong>Order ID:</strong> <code><?php echo htmlspecialchars($paymentResult['order_id']); ?></code></p>
                            <?php endif; ?>
                            <?php if (!empty($paymentResult['approval_link'])): ?>
                                <p><a href="<?php echo htmlspecialchars($paymentResult['approval_link']); ?>" class="btn btn-primary btn-sm" target="_blank">Complete Payment →</a></p>
                            <?php endif; ?>
                            <hr>
                            <p class="mb-0"><small>Now you would: save to database, send confirmation email, update order status, etc.</small></p>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php elseif ($paymentResult && !$paymentResult['success']): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading">⚠️ Payment Failed</h5>
                            <p><?php echo htmlspecialchars($paymentResult['error'] ?? 'Unknown error'); ?></p>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Payment Form -->
                    <div class="section-title">Step 1: Fill in Payment Details</div>
                    <form method="POST" id="paymentForm">
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
                            <small class="text-muted">Customer email for receipt</small>
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
                                <?php if (class_exists('PaymentGateway\Gateways\StripeGateway')): ?>
                                    <option value="stripe">Stripe</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description (optional)</label>
                            <input type="text" class="form-control" name="description" placeholder="Order #12345">
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">Process Payment Now</button>
                    </form>

                    <!-- How It Works -->
                    <div class="section-title">How This Works</div>
                    <div class="example-box">
                        <strong>The Package Handles:</strong>
                        <pre><code>// 1. Form submission to checkout.php (this file)
// 2. Package initializes the payment gateway
// 3. Package calls the gateway's charge() method
// 4. Gateway API processes the payment
// 5. Result displayed directly on this page

// Code in this file:
$result = $paymentManager->gateway($_POST['gateway'])
    ->charge([
        'amount' => $_POST['amount'],
        'currency' => $_POST['currency'],
        'customer' => [
            'email' => $_POST['email'],
            'name' => $_POST['name']
        ]
    ]);
</code></pre>
                    </div>

                    <!-- Next Steps -->
                    <div class="section-title">Next Steps in Your Application</div>
                    <div class="alert alert-info">
                        <strong>After payment is processed, you would:</strong>
                        <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                            <li>Save the transaction to database</li>
                            <li>Send confirmation email to customer</li>
                            <li>Update order status in your system</li>
                            <li>Trigger fulfillment process</li>
                            <li>Setup webhook handlers for payment updates</li>
                        </ul>
                        <strong style="display: block; margin-top: 1rem;">📚 See Examples:</strong>
                        <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                            <li><code>examples/laravel/</code> - Full Laravel integration with models</li>
                            <li><code>examples/symfony/</code> - Symfony controllers & services</li>
                            <li><code>docs/INTEGRATION_GUIDE.md</code> - Detailed setup guide</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
