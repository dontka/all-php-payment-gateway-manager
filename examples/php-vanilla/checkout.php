<?php

/**
 * PHP Vanilla Checkout Example
 *
 * Usage:
 *   php examples/php-vanilla/checkout.php
 *   Or access via web: http://localhost/examples/php-vanilla/checkout.php
 */

// Load configuration and dependencies
require_once __DIR__ . '/../../vendor/autoload.php';
$config = require_once __DIR__ . '/config.php';

use PaymentGateway\Core\PaymentManager;
use PaymentGateway\Gateways\PayPalGateway;
use PaymentGateway\Gateways\StripeGateway;

// Initialize Payment Manager
$paymentManager = new PaymentManager();
$initError = null;

// Configure gateways
try {
    // Setup PayPal
    $paypalGateway = new PayPalGateway([
        'client_id' => $config['paypal']['client_id'],
        'client_secret' => $config['paypal']['client_secret'],
        'mode' => $config['paypal']['mode']
    ]);
    $paymentManager->registerGateway('paypal', $paypalGateway);

    // Setup Stripe (if configured)
    if (!empty($config['stripe']['api_key'])) {
        $stripeGateway = new StripeGateway([
            'api_key' => $config['stripe']['api_key'],
            'secret_key' => $config['stripe']['secret_key']
        ]);
        $paymentManager->registerGateway('stripe', $stripeGateway);
    }

} catch (\Exception $e) {
    $initError = $e->getMessage();
    error_log("Payment gateway initialization error: " . $initError);
}

// Handle form submission or API request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if ($initError) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gateway initialization failed: ' . $initError]);
        exit;
    }
    
    $input = $_POST;

    // Validate input
    if (empty($input['amount']) || !is_numeric($input['amount'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid amount']);
        exit;
    }

    if (empty($input['currency'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing currency']);
        exit;
    }

    if (empty($input['email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing email']);
        exit;
    }

    $gateway = $input['gateway'] ?? 'paypal';

    try {
        // Process payment
        $result = $paymentManager->gateway($gateway)->charge([
            'amount' => (float) $input['amount'],
            'currency' => $input['currency'],
            'customer' => [
                'email' => $input['email'],
                'name' => $input['name'] ?? 'Customer'
            ],
            'description' => $input['description'] ?? 'Payment'
        ]);

        if ($result['success']) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'transaction_id' => $result['transaction_id'] ?? $result['order_id'] ?? null,
                'approval_link' => $result['approval_link'] ?? null,
                'message' => 'Payment created successfully'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $result['error'] ?? 'Payment failed'
            ]);
        }
        exit;

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Only show initialization error on GET if present
$initMessage = $initError ? "<div class=\"alert alert-warning\">⚠️ Gateway initialization error: $initError</div>" : '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Gateway Example</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container" style="margin-top: 2rem;">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <h1>💳 Payment Gateway</h1>
                <?php echo $initMessage; ?>

                <form method="POST" id="paymentForm">
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" name="amount" placeholder="99.99" step="0.01" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Currency</label>
                        <select class="form-control" name="currency" required>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="JPY">JPY</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="customer@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Name (optional)</label>
                        <input type="text" class="form-control" name="name" placeholder="John Doe">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Gateway</label>
                        <select class="form-control" name="gateway">
                            <option value="paypal">PayPal</option>
                            <option value="stripe">Stripe</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" <?php echo $initError ? 'disabled' : ''; ?>>Pay Now</button>
                </form>

                <div id="result" style="margin-top: 2rem;"></div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('paymentForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                const resultDiv = document.getElementById('result');

                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5>✅ Payment Created!</h5>
                            <p>Transaction ID: <code>${data.transaction_id}</code></p>
                            ${data.approval_link ? `
                                <a href="${data.approval_link}" class="btn btn-primary">
                                    Complete Payment →
                                </a>
                            ` : '<p>Payment completed immediately</p>'}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5>❌ Payment Failed</h5>
                            <p>${data.error}</p>
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('result').innerHTML = `
                    <div class="alert alert-danger">
                        <h5>❌ Request Error</h5>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>
