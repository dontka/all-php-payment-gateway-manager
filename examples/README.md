# Payment Gateway Package - Examples

This directory contains production-ready examples for integrating the Payment Gateway package with different technologies.

## Choose Your Framework

### 🚀 **PHP Vanilla** - Start Here!
- **Path**: [./php-vanilla/](./php-vanilla/)
- **Best For**: Learning, quick prototyping, understanding the core API
- **Dependencies**: None (just pure PHP!)
- **Setup Time**: 5 minutes
- **Features**:
  - Minimal setup required
  - Direct package usage
  - Form-based payment processing
  - Perfect for learning the API
- **Start Guide**: [php-vanilla/README.md](./php-vanilla/README.md)

### 🏗️ **Laravel** - Most Popular
- **Path**: [./laravel/](./laravel/)
- **Best For**: Laravel applications, web apps, REST APIs
- **Dependencies**: Laravel 9.0+, Eloquent ORM
- **Setup Time**: 15 minutes
- **Features**:
  - Facade access (`PaymentManager::gateway()`)
  - Service container integration
  - Database models for payments
  - Event listeners for payment status
  - Blade template examples
- **Start Guide**: [laravel/README.md](./laravel/README.md)

### ⚡ **Symfony** - Enterprise Apps
- **Path**: [./symfony/](./symfony/)
- **Best For**: Symfony applications, complex systems, DI containers
- **Dependencies**: Symfony 5.0+, Doctrine ORM
- **Setup Time**: 15 minutes
- **Features**:
  - Symfony service container injection
  - Event listener pattern
  - Doctrine ORM integration
  - Attribute-based routing
  - Controller examples
- **Start Guide**: [symfony/README.md](./symfony/README.md)

### 🏪 **WordPress/WooCommerce** - E-commerce
- **Path**: [./wordpress/](./wordpress/)
- **Best For**: WordPress sites, WooCommerce shops, plugins
- **Dependencies**: WordPress 5.0+, WooCommerce 4.0+
- **Setup Time**: 10 minutes
- **Features**:
  - WooCommerce payment gateway integration
  - Admin settings page
  - Automatic order status updates
  - Webhook handling
  - Debug logging
- **Start Guide**: [wordpress/README.md](./wordpress/README.md)

## Quick Feature Comparison

| Feature | PHP Vanilla | Laravel | Symfony | WordPress |
|---------|:---:|:---:|:---:|:---:|
| Learning Curve | Easy | Easy | Medium | Medium |
| Setup Time | 5 min | 15 min | 15 min | 10 min |
| Framework Required | No | Yes | Yes | Yes |
| Database Support | Optional | Yes | Yes | WP DB |
| Event System | No | Yes | Yes | Yes |
| Admin UI | No | Build | Build | Built-in |
| Production Ready | ✅ | ✅ | ✅ | ✅ |

## Getting Started by Use Case

### "I just want to see it work"
👉 Start with: [php-vanilla/README.md](./php-vanilla/README.md)

### "I'm using Laravel"
👉 Start with: [laravel/README.md](./laravel/README.md)

### "I'm using Symfony"
👉 Start with: [symfony/README.md](./symfony/README.md)

### "I'm running a WordPress site"
👉 Start with: [wordpress/README.md](./wordpress/README.md)

### "I'm not sure which framework to use"
1. Start with PHP Vanilla to understand the API
2. Then migrate to your preferred framework
3. Most concepts carry over seamlessly

## Common Tasks

### Process a Payment

**PHP Vanilla:**
```php
$paymentManager->gateway('paypal')->charge([
    'amount' => 99.99,
    'currency' => 'USD',
    'customer_email' => 'customer@example.com'
]);
```

**Laravel:**
```php
PaymentManager::gateway('paypal')->charge([...]);
```

**Symfony:**
```php
$paymentManager->gateway('paypal')->charge([...]);
```

**WordPress:**
```php
// Handled automatically in WooCommerce payment method
```

### Refund a Payment

**All Frameworks:**
```php
$paymentManager->gateway('paypal')->refund($transaction_id, [
    'amount' => 50.00  // partial refund
]);
```

### Check Payment Status

**All Frameworks:**
```php
$status = $paymentManager->gateway('paypal')->verify($transaction_id);
// Returns: ['status' => 'completed|pending|failed', ...]
```

### Handle Webhooks

**PHP Vanilla:**
```php
$paymentManager->gateway('paypal')->handleWebhook($_POST);
```

**Laravel:**
```php
// In controller
PaymentManager::gateway('paypal')->handleWebhook($request->all());
```

**Symfony:**
```php
// In controller
$this->paymentManager->gateway('paypal')->handleWebhook($data);
```

**WordPress:**
```php
// Automatically handled by plugin
```

## Configuration

All examples use a standard `.env` file for credentials:

```env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_secret
STRIPE_PUBLIC_KEY=your_public_key
STRIPE_SECRET_KEY=your_secret_key
```

See [.env.example](../.env.example) for complete configuration template.

## Supported Gateways

### ✅ PayPal (Fully Implemented)
- **Status**: Production-ready
- **Methods**: charge, refund, verify, handleWebhook
- **Features**: Multi-currency, order/capture model, full webhook support
- **Examples**: All frameworks have PayPal examples
- **Documentation**: [docs/PAYPAL.md](../docs/PAYPAL.md)

### 🔄 Stripe (In Development)
- **Status**: Coming soon (Week 2.1)
- **Methods**: charge, refund, verify, handleWebhook
- **Features**: Simple charge model, webhooks
- **Examples**: Will be added to all frameworks

### 🔄 Additional Gateways (Planned)
- Flutterwave (Week 3)
- PayStack (Week 3)
- Square (Week 4)
- Coinbase (Week 4)

## File Structure

```
examples/
├── README.md                    # This file
├── php-vanilla/
│   ├── checkout.php            # Payment form & processing
│   ├── config.php              # Configuration loader
│   └── README.md               # Setup guide
├── laravel/
│   ├── PaymentController.php   # Laravel controller
│   ├── routes.php              # Route definitions
│   ├── views/                  # Blade templates
│   └── README.md               # Setup guide
├── symfony/
│   ├── PaymentController.php   # Symfony controller
│   ├── templates/              # Twig templates
│   ├── config/                 # Service config
│   └── README.md               # Setup guide
└── wordpress/
    ├── payment-gateway.php     # Main plugin file
    ├── includes/               # Plugin classes
    ├── assets/                 # Admin styles
    └── README.md               # Setup guide
```

## Testing

### Before Going Live

1. ✅ Test with sandbox credentials
2. ✅ Process test payments
3. ✅ Test refunds
4. ✅ Test webhook handling
5. ✅ Check error scenarios
6. ✅ Review security settings

### Get Test Credentials

**PayPal:**
1. Go to [PayPal Developer Dashboard](https://developer.paypal.com)
2. Apps & Credentials → Sandbox
3. Create an app or use default
4. Copy Client ID and Secret

**Test Transactions:**
- Use sb-xxxxx@personal.example.com account
- Any amount will work ($0.01 - $9,999.99)
- No real money charged

## Troubleshooting

### Common Issues

**"Invalid credentials"**
- Verify .env file has correct values
- Check credentials in PayPal Dashboard
- Ensure mode is set to sandbox for testing

**"Framework not found"**
- Install framework: `composer install` in project root
- Check framework is registered in ServiceProvider

**"Database connection error"**
- Configure database credentials in .env
- Run migrations: `php artisan migrate` (Laravel)
- Check database exists and is accessible

**"Webhook not received"**
- Register webhook URL in PayPal account settings
- Ensure URL is publicly accessible
- Check server error logs for details

### Getting Help

1. Check the framework-specific README
2. Search [docs/INTEGRATION_GUIDE.md](../docs/INTEGRATION_GUIDE.md)
3. Review [docs/PAYPAL.md](../docs/PAYPAL.md) for PayPal-specific issues
4. Check error logs for detailed error messages

## Next Steps

### After Testing

1. **Add Database Storage**
   - Store payment records in your database
   - Track payment status over time
   - Audit trail for refunds

2. **Email Notifications**
   - Send confirmation emails to customers
   - Alert admin on payment failures
   - Provide invoice downloads

3. **Order Fulfillment**
   - Update order status when payment completes
   - Trigger shipping workflows
   - Manage inventory

4. **Multi-Gateway Support**
   - Let customers choose payment method
   - Fall back to alternate gateway
   - Track gateway performance

5. **Advanced Features**
   - Subscription payments
   - Tokenized payment storage
   - Recurring billing

See [docs/INTEGRATION_GUIDE.md](../docs/INTEGRATION_GUIDE.md) for detailed instructions.

## Resources

### Documentation
- 📖 [Main README](../README.md)
- 📖 [Integration Guide](../docs/INTEGRATION_GUIDE.md)
- 📖 [Quick Start Guide](../docs/QUICK_START_DETAILED.md)
- 📖 [PayPal Documentation](../docs/PAYPAL.md)

### External Resources
- 🔑 [PayPal Developer](https://developer.paypal.com)
- 💳 [Stripe Documentation](https://stripe.com/docs)
- 🚀 [Laravel Documentation](https://laravel.com/docs)
- ⚡ [Symfony Documentation](https://symfony.com/doc/current)
- 🏪 [WordPress Handbook](https://developer.wordpress.org/plugins/)
- 📦 [WooCommerce Docs](https://woocommerce.com/documentation/)

## Contributing

Found an issue in the examples?
1. Check the framework-specific README
2. Review the code for comments
3. Test with fresh credentials
4. Report issues with error logs

## License

All examples are MIT licensed - use freely in your projects!

