<?php

return [
    /*
     * Default payment gateway
     * Used when no specific gateway is selected
     */
    'default' => env('PAYMENT_GATEWAY', 'stripe'),

    /*
     * Payment gateways configuration
     */
    'gateways' => [
        // Stripe Gateway (Phase 1)
        'stripe' => [
            'class' => \PaymentGateway\Gateways\StripeGateway::class,
            'api_key' => env('STRIPE_API_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'enabled' => true,
        ],

        // PayPal Gateway (Phase 1)
        'paypal' => [
            'class' => \PaymentGateway\Gateways\PayPalGateway::class,
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
            'enabled' => true,
        ],

        // Flutterwave Gateway (Phase 1)
        'flutterwave' => [
            'class' => \PaymentGateway\Gateways\FlutterwaveGateway::class,
            'api_key' => env('FLUTTERWAVE_API_KEY'),
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'enabled' => false,
        ],

        // PayStack Gateway (Phase 1)
        'paystack' => [
            'class' => \PaymentGateway\Gateways\PayStackGateway::class,
            'api_key' => env('PAYSTACK_API_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'enabled' => false,
        ],

        // Coinbase Commerce Gateway (Phase 1)
        'coinbase' => [
            'class' => \PaymentGateway\Gateways\CoinbaseGateway::class,
            'api_key' => env('COINBASE_API_KEY'),
            'webhook_secret' => env('COINBASE_WEBHOOK_SECRET'),
            'enabled' => false,
        ],
    ],

    /*
     * Webhook configuration
     */
    'webhooks' => [
        'path' => env('WEBHOOK_PATH', '/webhooks'),
        'signature_header' => 'X-Webhook-Signature',
        'timestamp_header' => 'X-Webhook-Timestamp',
        'max_age' => 300, // 5 minutes
    ],

    /*
     * Logging configuration
     */
    'logging' => [
        'enabled' => true,
        'level' => env('LOG_LEVEL', 'debug'),
        'channel' => 'payment',
    ],

    /*
     * Retry configuration
     */
    'retry' => [
        'max_attempts' => 3,
        'delay' => 1000, // milliseconds
        'backoff_multiplier' => 2,
        'max_delay' => 30000,
    ],

    /*
     * Database configuration
     */
    'database' => [
        'table_prefix' => env('PAYMENT_TABLE_PREFIX', ''),
        'connection' => env('PAYMENT_DB_CONNECTION', 'default'),
    ],

    /*
     * Cache configuration
     */
    'cache' => [
        'driver' => env('CACHE_DRIVER', 'redis'),
        'ttl' => 3600, // 1 hour
    ],

    /*
     * Encryption configuration
     */
    'encryption' => [
        'key' => env('PAYMENT_ENCRYPTION_KEY'),
        'cipher' => 'AES-256-CBC',
    ],
];
