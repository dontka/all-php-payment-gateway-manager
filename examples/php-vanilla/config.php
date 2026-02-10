<?php

/**
 * Configuration for PHP Vanilla Examples
 *
 * Load environment variables from .env file or system environment
 * 
 * Setup Instructions:
 * 1. Copy .env.example to .env in project root
 * 2. Add your PayPal sandbox credentials from https://developer.paypal.com
 * 3. Uncomment and set STRIPE_API_KEY if using Stripe
 */

// Try to load .env file if it exists
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, ' "\'');
            
            // Only set if not already in environment
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

return [
    'paypal' => [
        'client_id' => getenv('PAYPAL_CLIENT_ID') ?: 'YOUR_PAYPAL_CLIENT_ID',
        'client_secret' => getenv('PAYPAL_CLIENT_SECRET') ?: 'YOUR_PAYPAL_CLIENT_SECRET',
        'mode' => getenv('PAYPAL_MODE') ?: 'sandbox' // sandbox or live
    ],
    'stripe' => [
        'api_key' => getenv('STRIPE_API_KEY') ?: '',
        'secret_key' => getenv('STRIPE_SECRET_KEY') ?: ''
    ],
    'square' => [
        'access_token' => getenv('SQUARE_ACCESS_TOKEN') ?: ''
    ],
    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'payment_gateway',
        'user' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: ''
    ]
];

