# PHP Vanilla Example - Setup Guide

## Quick Start

This example demonstrates how to use the Payment Gateway package in pure PHP (no framework required).

### Step 1: Get PayPal Sandbox Credentials

1. Go to [PayPal Developer Dashboard](https://developer.paypal.com)
2. Log in or create an account
3. Click "Apps & Credentials" (top menu)
4. Make sure "Sandbox" is selected (top right)
5. Click "Create App" under "REST API apps" section
6. Give it a name like "My PHP Payment App"
7. Click "Create App"
8. Copy these values:
   - **Client ID** - your app's client ID
   - **Secret** - your app's secret key

### Step 2: Configure Environment Variables

#### Option A: Using .env file (Recommended)

1. Copy `.env.example` to `.env` in the project root:
   ```bash
   cp .env.example .env
   ```

2. Open `.env` and replace:
   ```bash
   PAYPAL_CLIENT_ID=YOUR_SANDBOX_CLIENT_ID
   PAYPAL_CLIENT_SECRET=YOUR_SANDBOX_CLIENT_SECRET
   PAYPAL_MODE=sandbox
   ```

3. The example automatically loads from this `.env` file

#### Option B: Using System Environment Variables

Set environment variables on your system:

**Windows (PowerShell):**
```powershell
$env:PAYPAL_CLIENT_ID="your_client_id"
$env:PAYPAL_CLIENT_SECRET="your_client_secret"
$env:PAYPAL_MODE="sandbox"
```

**Linux/Mac (Bash):**
```bash
export PAYPAL_CLIENT_ID="your_client_id"
export PAYPAL_CLIENT_SECRET="your_client_secret"
export PAYPAL_MODE="sandbox"
```

### Step 3: Access the Example

Visit the example in your web browser:
```
http://localhost/examples/php-vanilla/checkout.php
```

Or run directly from CLI:
```bash
php examples/php-vanilla/checkout.php
```

### Step 4: Test Payment

1. Fill in the payment form
2. Click "Process Payment Now"
3. See the result displayed on the same page

## File Structure

```
examples/php-vanilla/
├── checkout.php       # Main payment form & processing
├── config.php         # Configuration loader (.env file parser)
└── README.md          # This file
```

## How It Works

1. **config.php** loads credentials from:
   - `.env` file (project root)
   - System environment variables
   - Falls back to defaults (for development)

2. **checkout.php**:
   - Initializes PaymentManager
   - Registers PayPal gateway
   - Processes payment on POST request
   - Displays result on same page

## Testing

### Test Credentials
PayPal Sandbox provides test accounts:
- **Business Account**: Receive test payments
- **Personal Account**: Send test payments

Get test accounts from Dashboard → Apps & Credentials → Accounts tab

### Test Payments
- Amount: Any value (e.g., 99.99)
- Currency: USD, EUR, GBP, JPY, etc.
- Email: Use your test personal account email
- The payment will NOT charge, just test the integration

## Troubleshooting

### Problem: "Payment processing failed: Failed to get PayPal access token"

**Solution**: Your credentials are invalid or missing
1. Verify your Client ID & Secret from PayPal Dashboard
2. Make sure PAYPAL_MODE is set to "sandbox" (for testing)
3. Check that .env file is in project root, not in examples/php-vanilla/
4. Reload the page and try again

### Problem: Unable to load .env file

**Solution**: 
1. Create `.env` in project root (not in examples/)
2. Copy from `.env.example`
3. Fill in your PayPal credentials

### Problem: Form shows "❌ Error" but no details

**Solution**:
- Check error logs in your web server
- In development, set `APP_DEBUG=true` in `.env`
- Look at browser console for JavaScript errors

## Next Steps

Once this basic example works:

1. **Save to Database**: Store transaction in database after success
2. **Send Email**: Send confirmation email to customer
3. **Handle Webhooks**: Listen to PayPal webhooks for payment updates
4. **Integration**: Use the same code in Laravel, Symfony, or your own framework

See [docs/INTEGRATION_GUIDE.md](../../docs/INTEGRATION_GUIDE.md) for framework-specific examples.

## Real PayPal Environment

When ready for production:

1. Apply for a live PayPal Business account
2. Get live credentials from PayPal Dashboard
3. In `.env`, change:
   ```bash
   PAYPAL_MODE=live
   PAYPAL_CLIENT_ID=your_live_client_id
   PAYPAL_CLIENT_SECRET=your_live_client_secret
   ```

⚠️ **Important**: Never commit `.env` with live credentials to version control!

## Security

- Never commit `.env` with real credentials
- Add `.env` to `.gitignore`:
  ```
  .env
  ```

- Use `.env.example` as a template instead

## Resources

- 📖 [PayPal Developer Docs](https://developer.paypal.com/docs/)
- 🔑 [PayPal Dashboard](https://www.paypal.com/signin)
- 💡 [Payment Gateway Package Docs](../../docs/)
- 🚀 [Framework Integration Guide](../../docs/INTEGRATION_GUIDE.md)
