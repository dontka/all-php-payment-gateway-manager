<?php

/**
 * Configuration for PHP Vanilla Examples
 *
 * Load environment variables from .env file or system environment
 */

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
    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'payment_gateway',
        'user' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: ''
    ]
];
