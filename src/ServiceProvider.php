<?php

declare(strict_types=1);

namespace PaymentGateway;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use PaymentGateway\Core\PaymentManager;

/**
 * Payment Gateway Service Provider
 *
 * Handles registration and bootstrapping of the payment gateway package
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/payment.php' => config_path('payment.php'),
        ], 'payment-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'payment-migrations');

        // Register migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register routes
        $this->loadRoutesFrom(__DIR__ . '/../src/Routes/api.php');
    }

    /**
     * Register any application services
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/payment.php',
            'payment'
        );

        // Register PaymentManager singleton
        $this->app->singleton('payment.manager', function ($app) {
            return new PaymentManager(
                $app['config']['payment'] ?? []
            );
        });

        // Register facade shortcut
        $this->app->alias('payment.manager', PaymentManager::class);
    }

    /**
     * Get services provided by this provider
     */
    public function provides(): array
    {
        return ['payment.manager', PaymentManager::class];
    }
}
