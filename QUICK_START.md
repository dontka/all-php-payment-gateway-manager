# 🚀 GUIDE DE DÉMARRAGE RAPIDE - Intégration

Intégrez les paiements digitaux en **moins de 5 minutes** dans votre projet PHP.

---

## 📋 Choix Rapide

Sélectionnez votre framework / setup :

- 🔷 **PHP Vanilla** → [Sans Framework](#-php-vanilla)
- 🔵 **Laravel** → [Setup Laravel](#-laravel-setup) (recommandé)
- 🟣 **Symfony** → [Setup Symfony](#-symfony-setup)
- 🔴 **WordPress** → [Plugin WordPress](#-wordpress-plugin)

---

## 🔷 Laravel Setup

### Étape 1 : Installation (30 sec)

```bash
composer require dontka/all-php-payment-gateway-manager
```

### Étape 2 : Configuration (1 min)

```bash
php artisan vendor:publish --provider="PaymentGateway\ServiceProvider"
php artisan migrate
```

### Étape 3 : Configurer (1 min)

```env
# .env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_sandbox_client_id
PAYPAL_CLIENT_SECRET=your_sandbox_secret
```

### Étape 4 : Premier Paiement (2 min)

```php
<?php
// app/Http/Controllers/CheckoutController.php

namespace App\Http\Controllers;

use PaymentGateway\Facades\Payment;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function pay(Request $request)
    {
        $result = Payment::charge([
            'amount' => 99.99,
            'currency' => 'EUR',
            'customer' => ['email' => auth()->user()->email]
        ]);

        if ($result['success']) {
            return redirect($result['approval_link']);
        }

        return back()->with('error', '❌ Payment failed');
    }
}
```

**✅ C'est tout!** Vous acceptez les paiements. ✨

---

## 🔹 Symfony Setup

### Installation

```bash
composer require dontka/all-php-payment-gateway-manager symfony/http-client
```

### Configuration

```yaml
# config/services.yaml
services:
  payment.manager:
    class: PaymentGateway\Core\PaymentManager
    public: true
```

### Utilisation

```php
public function charge(Request $request, PaymentManager $manager): Response
{
    $result = $manager->charge([
        'amount' => 99.99,
        'currency' => 'EUR'
    ]);

    return new Response('✅ Payment OK');
}
```

---

## 🔸 PHP Vanilla

### Installation

```bash
composer require dontka/all-php-payment-gateway-manager
```

### Utilisation

```php
<?php
require_once 'vendor/autoload.php';

use PaymentGateway\Core\PaymentManager;
use PaymentGateway\Gateways\PayPalGateway;

// Setup
$manager = new PaymentManager();
$paypal = new PayPalGateway(
    apiKey: getenv('PAYPAL_CLIENT_ID'),
    secret: getenv('PAYPAL_CLIENT_SECRET'),
    mode: 'sandbox'
);

$manager->registerGateway('paypal', $paypal);

// Process payment
$result = $manager->charge([
    'gateway' => 'paypal',
    'amount' => 99.99,
    'currency' => 'EUR'
]);

echo $result['success'] ? "✅ OK" : "❌ Error";
?>
```

---

## 🔴 WordPress Plugin

### Installation

```bash
mkdir wp-content/plugins/payment-gateway
cd wp-content/plugins/payment-gateway
composer require dontka/all-php-payment-gateway-manager
```

### Enregistrement

Créer `payment-gateway.php` :

```php
<?php
/**
 * Plugin Name: Payment Gateway
 * Description: PayPal & Stripe pour WooCommerce
 * Version: 1.0.0
 */

require_once __DIR__ . '/vendor/autoload.php';

use PaymentGateway\Core\PaymentManager;

class PaymentPlugin {
    public function __construct() {
        add_action('woocommerce_init', [$this, 'init']);
    }

    public function init() {
        $manager = new PaymentManager();
        // Setup gateways...
    }
}

new PaymentPlugin();
?>
```

Puis activer dans WordPress → Plugins.

---

## 📚 Documentation Complète

| Guide | Lien |
|-------|------|
| **Intégration Détaillée** | [docs/INTEGRATION_GUIDE.md](docs/INTEGRATION_GUIDE.md) |
| **Guide 5 Minutes** | [docs/QUICK_START_DETAILED.md](docs/QUICK_START_DETAILED.md) |
| **Cas d'Usage Avancés** | [docs/API.md](docs/API.md) |
| **Webhooks** | [docs/WEBHOOKS.md](docs/WEBHOOKS.md) |
| **Sécurité** | [docs/SECURITY.md](docs/SECURITY.md) |

---

## ✅ Vérifier Rapidement

### Test en Laravel

```bash
php artisan tinker
>>> Payment::charge(['amount' => 10, 'currency' => 'USD'])
```

### Vérifier la Base de Données

```bash
# Laravel
php artisan tinker
>>> DB::table('payments')->latest()->get()
```

---

## ❓ Questions Fréquentes

**Q: Quels gateways?**  
R: PayPal ✅, Stripe ✅, et plus à venir.

**Q: Sécurisé?**  
R: Oui! Chiffrement AES-256, validation complète, webhooks sécurisés.

**Q: Multi-devise?**  
R: Oui! 20+ devises supportées automatiquement.

**Q: Sandbox pour tester?**  
R: Oui! Inclus par défaut.

---

## 📞 Besoin d'Aide?

- 📖 [Documentation Complète](docs/INTEGRATION_GUIDE.md)
- 🐛 [GitHub Issues](https://github.com/dontka/all-php-payment-gateway-manager/issues)
- 💬 [Discussions](https://github.com/dontka/all-php-payment-gateway-manager/discussions)

---

**Vous êtes prêt à accepter les paiements!** 🚀

