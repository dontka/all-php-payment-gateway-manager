# 🚀 Getting Started - Payment Gateway Manager

Welcome! This guide will help you get up and running in **5 minutes**.

## Choose Your Path

### 1. **I just want to see it working** 
   - ⏱️ Time: 5 minutes
   - 📌 Best for: Understanding the API
   - 👉 **Start here**: [PHP Vanilla Example](examples/php-vanilla/README.md)

### 2. **I'm using Laravel**
   - ⏱️ Time: 15 minutes
   - 📌 Best for: Laravel applications
   - 👉 **Start here**: [Laravel Example](examples/laravel/README.md)

### 3. **I'm using Symfony**
   - ⏱️ Time: 15 minutes
   - 📌 Best for: Symfony applications
   - 👉 **Start here**: [Symfony Example](examples/symfony/README.md)

### 4. **I'm running WordPress**
   - ⏱️ Time: 10 minutes
   - 📌 Best for: WordPress/WooCommerce sites
   - 👉 **Start here**: [WordPress Plugin Example](examples/wordpress/README.md)

---

## 5-Minute Quickstart (PHP Vanilla)

If you just want to see it work right now:

### Step 1: Get PayPal Credentials (2 min)

1. Go to [PayPal Developer Dashboard](https://developer.paypal.com)
2. Log in or create account
3. Click "Apps & Credentials" at the top
4. Select "Sandbox" mode (top right)
5. Click "Create App" button
6. Copy your **Client ID** and **Secret**

### Step 2: Create .env File (1 min)

In your project root, create a `.env` file:

```env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_client_id_here
PAYPAL_CLIENT_SECRET=your_secret_here
```

### Step 3: Run the Example (1 min)

```bash
php examples/php-vanilla/checkout.php
```

Or open in browser:
```
http://localhost/examples/php-vanilla/checkout.php
```

### Step 4: Test Payment (1 min)

1. Fill in the form on the page
2. Click "Process Payment Now"
3. See the result display on the same page

**That's it!** You've successfully processed a test payment. 🎉

---

## What's Next?

### Want to understand the code better?
- Read [examples/php-vanilla/README.md](examples/php-vanilla/README.md)
- Review the source code in `src/Gateways/PayPalGateway.php`

### Ready to integrate into your project?
- Choose your framework above
- Follow the corresponding example guide
- Use the same API in your own code

### Need more details?
- See [Integration Guide](docs/INTEGRATION_GUIDE.md) for all frameworks
- Check [docs/PAYPAL.md](docs/PAYPAL.md) for PayPal-specific details
- Review [README.md](README.md) for complete documentation

---

## Common Questions

### "Do I need to install the whole package?"
No! The examples are **completely standalone**. You can copy just the `src/` directory and use the code directly in your project.

### "Will this work without Laravel?"
Yes! Start with [PHP Vanilla](examples/php-vanilla/README.md) to see the raw package in action. No framework required.

### "What if I'm using Wordpress?"
Perfect! We have a [WordPress plugin example](examples/wordpress/README.md) that integrates with WooCommerce.

### "How do I get live credentials?"
Once testing is done with sandbox credentials:
1. Get a live PayPal Business account
2. Generate live credentials from PayPal Dashboard
3. Update your `.env` file with `PAYPAL_MODE=live` and live credentials

### "Is this production-ready?"
Yes! This is a complete, production-ready payment gateway integration with:
- ✅ Full error handling
- ✅ Security validation
- ✅ Webhook support
- ✅ Comprehensive logging
- ✅ 34+ unit tests
- ✅ 85%+ code coverage

---

## Typical Integration Flow

```
1. Choose Framework (Laravel/Symfony/PHP/WordPress)
     ↓
2. Copy Example Code
     ↓
3. Get PayPal Credentials
     ↓
4. Configure .env File
     ↓
5. Run Example/Test
     ↓
6. Integrate into Your Project
     ↓
7. Deploy to Production
```

---

## File Organization

```
project-root/
├── examples/                    # Standalone examples for all frameworks
│   ├── php-vanilla/            # Plain PHP (START HERE!)
│   ├── laravel/                # Laravel integration
│   ├── symfony/                # Symfony integration
│   └── wordpress/              # WordPress plugin
│
├── src/                         # Source package code
│   ├── Gateways/              # Payment gateway implementations
│   │   └── PayPalGateway.php  # PayPal integration
│   ├── Models/                # Database models
│   ├── Events/                # Event classes
│   ├── Facades/               # Laravel facades
│   └── ...
│
├── docs/                        # Full documentation
│   ├── INTEGRATION_GUIDE.md    # Framework guides
│   ├── PAYPAL.md              # PayPal docs
│   └── ...
│
├── README.md                    # Main documentation
├── .env.example                 # Configuration template
└── composer.json               # Package definition
```

---

## Supported Payment Methods

### ✅ Fully Implemented
- **PayPal** → Ready to use now

### 🔄 Coming Soon
- **Stripe** (Week 2.1)
- **Square** (Week 2.1)
- **Flutterwave** (Week 3)
- **PayStack** (Week 3)
- **Coinbase** (Week 4)
- **Wise** (Week 4)

---

## Key Features

| Feature | Status |
|---------|--------|
| PayPal Integration | ✅ Complete |
| Stripe Integration | 🔄 In Progress |
| Multi-gateway Support | ✅ Complete |
| Webhook Handling | ✅ Complete |
| Refunds | ✅ Complete |
| Laravel Support | ✅ Complete |
| Symfony Support | ✅ Complete |
| WordPress Plugin | ✅ Complete |
| Unit Tests | ✅ 34+ tests |
| Documentation | ✅ Complete |

---

## Troubleshooting

### Payment fails with "401 Unauthorized"
- Check your Client ID and Secret in `.env`
- Verify credentials match what's in PayPal Dashboard
- Ensure `PAYPAL_MODE` is set to `sandbox` for testing

### "Call to undefined function..."
- Make sure you're in the right directory
- Run the example with correct path
- Check PHP version is 8.1+

### The example won't load in browser
- Ensure you have a local web server (Apache/Nginx)
- Check the URL is correct
- Verify `.env` file is in project root (not in examples/)

### Having other issues?
Check the framework-specific README:
- [PHP Vanilla Help](examples/php-vanilla/README.md#troubleshooting)
- [Laravel Help](examples/laravel/README.md#troubleshooting)
- [Symfony Help](examples/symfony/README.md#troubleshooting)
- [WordPress Help](examples/wordpress/README.md#troubleshooting)

---

## Learning Resources

| Resource | What You'll Learn |
|----------|------------------|
| [examples/README.md](examples/README.md) | Overview of all examples |
| [examples/php-vanilla/README.md](examples/php-vanilla/README.md) | Core API concepts |
| [docs/INTEGRATION_GUIDE.md](docs/INTEGRATION_GUIDE.md) | Framework integration details |
| [docs/PAYPAL.md](docs/PAYPAL.md) | PayPal-specific information |
| [PayPal Developer Docs](https://developer.paypal.com/docs) | Official PayPal documentation |

---

## Next Steps

1. **Choose your starting point** (above) based on your needs
2. **Get PayPal sandbox credentials** from the developer dashboard
3. **Create `.env` file** with your credentials
4. **Run the example** and test a payment
5. **Review the code** to understand how it works
6. **Integrate into your project** using the same patterns

---

## Still Need Help?

- 📖 Read the [main README](README.md)
- 📚 Check the [Integration Guide](docs/INTEGRATION_GUIDE.md)
- 🔗 Visit [PayPal Developer Dashboard](https://developer.paypal.com)
- 💬 Open an [Issue on GitHub](https://github.com/dontka/all-php-payment-gateway-manager/issues)

---

## Ready? Let's Go! 🚀

Pick your path above and get started. You'll have a working payment integration in 5-15 minutes!

**Happy coding!** 💳
