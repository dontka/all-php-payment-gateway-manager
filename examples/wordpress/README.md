# WordPress/WooCommerce Payment Gateway Integration

This example demonstrates how to use the Payment Gateway package as a WordPress plugin for WooCommerce.

## Installation

### Step 1: Copy Plugin Files

1. Copy the plugin directory to WordPress:
   ```
   wp-content/plugins/payment-gateway/
   ```

2. Files needed:
   ```
   wp-content/plugins/payment-gateway/
   ├── payment-gateway.php        # Main plugin file
   ├── vendor/                    # Composer autoloader
   ├── includes/
   │   ├── Payment_Gateway.php    # Main gateway class
   │   └── Admin.php              # Settings page
   └── assets/
       └── style.css              # Admin styles (optional)
   ```

### Step 2: Install Dependencies

```bash
cd wp-content/plugins/payment-gateway
composer install
```

### Step 3: Activate Plugin

1. Go to WordPress Admin → Plugins
2. Find "Payment Gateway Manager"
3. Click "Activate"

## Configuration

### Step 1: Get PayPal Credentials

1. Visit [PayPal Developer Dashboard](https://developer.paypal.com)
2. Log in or create account
3. Get your:
   - Client ID
   - Client Secret
   - App ID (optional)

### Step 2: Fill Plugin Settings

1. Go to WordPress Admin → WooCommerce → Settings
2. Click "Payments" tab
3. Find "Payment Gateway Manager"
4. Click "Manage"
5. Enable the gateway
6. Fill in:
   - **Client ID**: From PayPal Dashboard
   - **Client Secret**: From PayPal Dashboard
   - **Mode**: sandbox (for testing) or live (for production)
   - **Enable Debug Logging**: Check for troubleshooting

7. Click "Save"

## Main Plugin File

```php
<?php
/**
 * Plugin Name: Payment Gateway Manager
 * Plugin URI: https://github.com/yourname/payment-gateway
 * Description: Flexible payment gateway integration for WooCommerce
 * Version: 1.0.0
 * Author: Your Name
 * License: MIT
 *
 * @package PaymentGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

// Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

class Payment_Gateway_Manager
{
    public function __construct()
    {
        add_filter('woocommerce_payment_gateways', [$this, 'register_gateway']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function register_gateway($gateways)
    {
        $gateways[] = 'WC_Payment_Gateway_Manager';
        return $gateways;
    }

    public function enqueue_admin_assets()
    {
        wp_enqueue_style(
            'payment-gateway-admin',
            plugin_dir_url(__FILE__) . 'assets/style.css'
        );
    }
}

new Payment_Gateway_Manager();

// Load the WooCommerce gateway class
require_once __DIR__ . '/includes/class-wc-payment-gateway-manager.php';
```

## WooCommerce Gateway Class

```php
<?php
// includes/class-wc-payment-gateway-manager.php

use PaymentGateway\PaymentManager;
use PaymentGateway\Gateways\PayPalGateway;

class WC_Payment_Gateway_Manager extends WC_Payment_Gateway
{
    private $payment_manager;

    public function __construct()
    {
        $this->id                 = 'payment_gateway_manager';
        $this->icon               = '';
        $this->has_fields         = false;
        $this->method_title       = 'Payment Gateway Manager';
        $this->method_description = 'Accept payments via PayPal, Stripe, and more';

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Initialize payment manager
        $this->payment_manager = new PaymentManager();
        $this->payment_manager->registerGateway('paypal', new PayPalGateway([
            'mode' => $this->get_option('paypal_mode'),
            'client_id' => $this->get_option('paypal_client_id'),
            'client_secret' => $this->get_option('paypal_client_secret'),
        ]));

        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, 
            [$this, 'process_admin_options']);
        add_action('woocommerce_receipt_' . $this->id, 
            [$this, 'receipt_page']);
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable Payment Gateway Manager', 'woocommerce'),
                'default' => 'yes'
            ],
            'title' => [
                'title'       => __('Title', 'woocommerce'),
                'type'        => 'text',
                'description' => __('This controls the title displayed to the customer', 'woocommerce'),
                'default'     => __('Payment Gateway Manager', 'woocommerce'),
            ],
            'description' => [
                'title'       => __('Description', 'woocommerce'),
                'type'        => 'textarea',
                'description' => __('Payment gateway description', 'woocommerce'),
                'default'     => __('Pay securely using PayPal or Stripe', 'woocommerce'),
            ],
            'paypal_client_id' => [
                'title'       => __('PayPal Client ID', 'woocommerce'),
                'type'        => 'password',
                'description' => __('Get from PayPal Developer Dashboard', 'woocommerce'),
            ],
            'paypal_client_secret' => [
                'title'       => __('PayPal Client Secret', 'woocommerce'),
                'type'        => 'password',
                'description' => __('Get from PayPal Developer Dashboard', 'woocommerce'),
            ],
            'paypal_mode' => [
                'title'       => __('PayPal Mode', 'woocommerce'),
                'type'        => 'select',
                'options'     => [
                    'sandbox' => __('Sandbox (Testing)', 'woocommerce'),
                    'live'    => __('Live (Production)', 'woocommerce'),
                ],
                'description' => __('Use sandbox for testing', 'woocommerce'),
                'default'     => 'sandbox',
            ],
            'debug' => [
                'title'   => __('Debug Logging', 'woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable debug logging', 'woocommerce'),
                'default' => 'no'
            ],
        ];
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        try {
            // Process payment
            $payment = $this->payment_manager->gateway('paypal')->charge([
                'amount' => (float)$order->get_total(),
                'currency' => $order->get_currency(),
                'customer_email' => $order->get_billing_email(),
                'description' => 'Order #' . $order_id,
                'customer_name' => $order->get_billing_first_name(),
            ]);

            // Store transaction ID
            $order->update_meta_data('_payment_transaction_id', $payment->transaction_id);
            $order->save_meta_data();

            // Mark as complete
            $order->payment_complete($payment->transaction_id);

            // Remove cart
            WC()->cart->empty_cart();

            // Log success
            if ('yes' === $this->get_option('debug')) {
                error_log("Payment successful for order {$order_id}: {$payment->transaction_id}");
            }

            return [
                'result'   => 'success',
                'redirect' => $this->get_return_url($order)
            ];

        } catch (\Exception $e) {
            wc_add_notice(__('Payment error: ', 'woocommerce') . $e->getMessage(), 'error');
            
            if ('yes' === $this->get_option('debug')) {
                error_log("Payment error for order {$order_id}: " . $e->getMessage());
            }

            return [
                'result'   => 'failure',
                'redirect' => ''
            ];
        }
    }

    public function receipt_page($order_id)
    {
        $order = wc_get_order($order_id);
        echo _e('Thank you for your order.', 'woocommerce');
        echo ' ' . sprintf(__('Your transaction ID is: %s', 'woocommerce'), 
            $order->get_meta('_payment_transaction_id'));
    }
}
```

## Features

### Available Payment Methods

- ✅ PayPal (fully implemented)
- 🔄 Stripe (in development)
- 🔄 Square (in development)
- 🔄 Flutterwave (in development)

### Supported Operations

- **Charge**: Create and complete payments
- **Refund**: Issue full or partial refunds
- **Verify**: Check payment status
- **Webhooks**: Auto-update order status

## Testing

### Test Mode Details

When `paypal_mode` is set to `sandbox`:

1. **Use Test Account**
   ```
   Email: sb-xxxxx@personal.example.com
   Password: 123456
   ```

2. **Any amount will work**: $0.01 to $9,999.99

3. **Check WooCommerce Orders**
   - Go to Orders page
   - Find test order
   - See transaction ID and status

### Test Scenarios

1. **Successful Payment**
   - Enter test buyer details
   - Click checkout
   - See "Order received" message

2. **Declined Payment**
   - Use test account with declined setup
   - See error message
   - Order stays in pending status

3. **Refund Test**
   - Process payment
   - Go to order details
   - Click "Refund"
   - Verify refund processed

## Admin Settings

### Debug Logging

When enabled, logs appear in:
```
wp-content/debug.log
```

Example log entry:
```
Payment successful for order 123: PayPal-xxx-transaction-id
```

### Settings Page Location

1. WordPress Admin → WooCommerce → Settings
2. Click "Payments" tab
3. Click "Payment Gateway Manager" → Manage

Fields available:
- Enable/Disable toggle
- Title (shown to customer)
- Description
- PayPal credentials
- Mode selector
- Debug logging

## Webhook Handling

WordPress/WooCommerce webhook handler is automatically set up.

### How It Works

1. PayPal sends webhook to your site
2. Plugin receives event
3. Payment status updated in WooCommerce
4. Order automatically marked complete if payment successful

### Webhook URL

Configured at: `https://your-site.com/wp-json/payment-gateway/webhook`

## Troubleshooting

### Issue: "PayPal credentials not configured"

**Solution:**
1. Go to WooCommerce → Settings → Payments
2. Click "Payment Gateway Manager" → Manage
3. Enter PayPal Client ID and Client Secret
4. Save changes
5. Reload checkout page

### Issue: Payment fails with "401 Unauthorized"

**Solution:**
1. Verify credentials are correct in PayPal Dashboard
2. Check that mode is set to `sandbox` for testing
3. Ensure credentials are copied without extra spaces
4. Check PayPal account is active

### Issue: Order not updating after payment

**Solution:**
1. Enable debug logging
2. Check `wp-content/debug.log` for errors
3. Verify webhook URL is set correctly
4. Check PayPal webhook settings

### Issue: Refund not processing

**Solution:**
1. Transaction must be completed first
2. Amount must be less than original charge
3. Check PayPal permissions allow refunds
4. See debug log for specific error

## Plugin Filters & Hooks

```php
// Custom payment amount before charge
apply_filters('payment_gateway_charge_amount', $amount, $order);

// Custom description
apply_filters('payment_gateway_description', $description, $order);

// After successful payment
do_action('payment_gateway_success', $order, $transaction_id);

// On payment failure
do_action('payment_gateway_failure', $order, $error);
```

## Update to Live Mode

When ready for production:

1. Create live PayPal account
2. Get live credentials
3. Go to WooCommerce → Settings → Payments
4. Change mode from `sandbox` to `live`
5. Enter live credentials
6. Save

⚠️ **Important**: Test thoroughly in sandbox first!

## Resources

- 📖 [Package Documentation](../../docs/)
- 🔑 [PayPal Developer Dashboard](https://developer.paypal.com)
- 📦 [WooCommerce Documentation](https://woocommerce.com/documentation/)
- 🏪 [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
