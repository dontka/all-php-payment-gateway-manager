<?php

/**
 * WordPress / WooCommerce Payment Gateway Plugin
 *
 * Plugin Name: Payment Gateway Manager Integration
 * Plugin URI: https://github.com/dontka/all-php-payment-gateway-manager
 * Description: Unified payment gateway for PayPal, Stripe, and more
 * Version: 1.0.0
 * Author: Your Company
 * License: MIT
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use PaymentGateway\Core\PaymentManager;
use PaymentGateway\Gateways\PayPalGateway;
use PaymentGateway\Events\PaymentSuccessEvent;
use PaymentGateway\Events\PaymentFailedEvent;

class PaymentGatewayManagerPlugin {

    private $paymentManager;

    public function __construct() {
        // Initialize on WordPress loaded
        add_action('wp_loaded', [$this, 'init']);

        // Register WooCommerce gateways
        add_filter('woocommerce_payment_gateways', [$this, 'registerGateways']);

        // Register admin menu
        add_action('admin_menu', [$this, 'addAdminMenu']);

        // Register settings
        add_action('admin_init', [$this, 'registerSettings']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);

        // REST API endpoint for webhooks
        add_action('rest_api_init', [$this, 'registerWebhookEndpoint']);
    }

    /**
     * Initialize Payment Manager
     */
    public function init() {
        $this->paymentManager = new PaymentManager();

        // Setup PayPal gateway
        try {
            $paypalGateway = new PayPalGateway(
                apiKey: get_option('payment_gateway_paypal_key'),
                secret: get_option('payment_gateway_paypal_secret'),
                mode: get_option('payment_gateway_mode', 'sandbox')
            );
            $this->paymentManager->registerGateway('paypal', $paypalGateway);
        } catch (\Exception $e) {
            error_log('PayPal gateway error: ' . $e->getMessage());
        }
    }

    /**
     * Register WooCommerce payment gateways
     */
    public function registerGateways($methods) {
        $methods[] = 'WC_Payment_Gateway_Manager_PayPal';
        $methods[] = 'WC_Payment_Gateway_Manager_Stripe';
        return $methods;
    }

    /**
     * Add admin menu
     */
    public function addAdminMenu() {
        add_menu_page(
            'Payment Gateways',
            'Payment Gateways',
            'manage_options',
            'payment-gateways',
            [$this, 'renderAdminPage'],
            'dashicons-credit',
            56
        );
    }

    /**
     * Render admin page
     */
    public function renderAdminPage() {
        ?>
        <div class="wrap">
            <h1>💳 Payment Gateway Manager</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('payment_gateway_settings'); ?>
                <?php do_settings_sections('payment_gateway_settings'); ?>
                <?php submit_button(); ?>
            </form>

            <h2>Recent Transactions</h2>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Gateway</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- List recent transactions -->
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Register settings
     */
    public function registerSettings() {
        register_setting('payment_gateway_settings', 'payment_gateway_mode');
        register_setting('payment_gateway_settings', 'payment_gateway_paypal_key');
        register_setting('payment_gateway_settings', 'payment_gateway_paypal_secret');
        register_setting('payment_gateway_settings', 'payment_gateway_stripe_key');
        register_setting('payment_gateway_settings', 'payment_gateway_stripe_secret');

        add_settings_section(
            'payment_gateway_section',
            'Gateway Configuration',
            null,
            'payment_gateway_settings'
        );

        add_settings_field(
            'payment_gateway_mode',
            'Mode',
            [$this, 'renderModeField'],
            'payment_gateway_settings',
            'payment_gateway_section'
        );

        add_settings_field(
            'payment_gateway_paypal_key',
            'PayPal Client ID',
            [$this, 'renderPayPalKeyField'],
            'payment_gateway_settings',
            'payment_gateway_section'
        );

        add_settings_field(
            'payment_gateway_paypal_secret',
            'PayPal Secret',
            [$this, 'renderPayPalSecretField'],
            'payment_gateway_settings',
            'payment_gateway_section'
        );
    }

    public function renderModeField() {
        $value = get_option('payment_gateway_mode', 'sandbox');
        ?>
        <select name="payment_gateway_mode">
            <option value="sandbox" <?php selected($value, 'sandbox'); ?>>Sandbox</option>
            <option value="live" <?php selected($value, 'live'); ?>>Live</option>
        </select>
        <?php
    }

    public function renderPayPalKeyField() {
        $value = get_option('payment_gateway_paypal_key');
        echo '<input type="password" name="payment_gateway_paypal_key" value="' . esc_attr($value) . '" />';
    }

    public function renderPayPalSecretField() {
        $value = get_option('payment_gateway_paypal_secret');
        echo '<input type="password" name="payment_gateway_paypal_secret" value="' . esc_attr($value) . '" />';
    }

    /**
     * Enqueue assets
     */
    public function enqueueAssets() {
        wp_enqueue_script('payment-gateway', plugin_dir_url(__FILE__) . 'assets/payment.js');
        wp_enqueue_style('payment-gateway', plugin_dir_url(__FILE__) . 'assets/payment.css');
    }

    /**
     * Register REST API endpoint for webhooks
     */
    public function registerWebhookEndpoint() {
        register_rest_route('payment/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handleWebhook'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * Handle webhook
     */
    public function handleWebhook($request) {
        try {
            $payload = $request->get_json_params();
            $gateway = isset($request->get_params()['gateway']) ? 'paypal' : 'stripe';

            $result = $this->paymentManager->gateway($gateway)->handleWebhook(
                $payload,
                $request->get_headers()
            );

            if ($result['success']) {
                // Find WooCommerce order
                $orders = wc_get_orders([
                    'meta_key' => '_payment_transaction_id',
                    'meta_value' => $result['transaction_id']
                ]);

                if ($orders) {
                    $order = $orders[0];

                    if ($result['status'] === 'completed') {
                        $order->payment_complete($result['transaction_id']);
                    } else if ($result['status'] === 'failed') {
                        $order->update_status('failed');
                    } else if ($result['status'] === 'refunded') {
                        $order->update_status('refunded');
                    }
                }

                return new \WP_REST_Response(['status' => 'ok'], 200);
            }

            return new \WP_REST_Response(['error' => $result['error']], 400);

        } catch (\Exception $e) {
            error_log('Webhook error: ' . $e->getMessage());
            return new \WP_REST_Response(['error' => 'Processing error'], 200);
        }
    }

    /**
     * Get Payment Manager instance
     */
    public function getPaymentManager() {
        return $this->paymentManager;
    }
}

// Initialize plugin
new PaymentGatewayManagerPlugin();

/**
 * Global function to access Payment Manager
 */
function get_payment_manager() {
    global $payment_gateway_plugin;
    if (!$payment_gateway_plugin) {
        $payment_gateway_plugin = new PaymentGatewayManagerPlugin();
    }
    return $payment_gateway_plugin->getPaymentManager();
}
