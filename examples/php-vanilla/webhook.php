<?php

/**
 * PHP Vanilla Webhook Handler Example
 *
 * Usage:
 *   Configure in PayPal/Stripe Dashboard:
 *   http://your-domain.com/examples/php-vanilla/webhook.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PaymentGateway\Core\PaymentManager;
use PaymentGateway\Gateways\PayPalGateway;

// Simple logging function
function log_webhook($message, $data = []) {
    $log = fopen(__DIR__ . '/webhook.log', 'a');
    fwrite($log, date('Y-m-d H:i:s') . ' - ' . $message . "\n");
    if ($data) {
        fwrite($log, json_encode($data, JSON_PRETTY_PRINT) . "\n");
    }
    fclose($log);
}

try {
    // Get request payload
    $payload = json_decode(file_get_contents('php://input'), true);
    $headers = getallheaders();

    log_webhook('Webhook received', ['headers' => array_keys($headers)]);

    // Detect gateway
    $gateway = 'paypal';
    if (isset($headers['Stripe-Signature'])) {
        $gateway = 'stripe';
    }

    // Initialize Payment Manager
    $config = require 'config.php';
    $paymentManager = new PaymentManager();

    // Setup PayPal gateway
    $paypalGateway = new PayPalGateway(
        apiKey: $config['paypal']['client_id'],
        secret: $config['paypal']['client_secret'],
        mode: $config['paypal']['mode']
    );
    $paymentManager->registerGateway('paypal', $paypalGateway);

    // Process webhook
    $result = $paymentManager->gateway($gateway)->handleWebhook($payload, $headers);

    if ($result['success']) {
        log_webhook('Webhook processed successfully', [
            'event_type' => $result['event_type'] ?? null,
            'transaction_id' => $result['transaction_id'] ?? null,
            'status' => $result['status'] ?? null
        ]);

        // Update database or perform action
        $order_data = [
            'transaction_id' => $result['transaction_id'] ?? null,
            'status' => $result['status'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        log_webhook('Database update', $order_data);

        // Example: Save to database
        // $pdo->prepare('UPDATE orders SET status = :status WHERE transaction_id = :id')
        //     ->execute($order_data);

        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    } else {
        log_webhook('Webhook processing failed', $result);

        http_response_code(400);
        echo json_encode(['error' => $result['error'] ?? 'Failed']);
    }

} catch (\Exception $e) {
    log_webhook('Webhook error', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    // Always return 200 to prevent webhook retries
    http_response_code(200);
    echo json_encode(['error' => 'Processing error']);
}
