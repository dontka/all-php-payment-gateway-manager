# 🔗 Guide d'Intégration Complet

Comment intégrer le **PHP Payment Gateway Manager** dans votre projet existant.

---

## 📋 Table des Matières

1. [Pour Projets Laravel](#-projets-laravel)
2. [Pour Projets Symfony](#-projets-symfony)
3. [Pour PHP Vanilla](#-php-vanilla)
4. [Pour WordPress](#-wordpress)
5. [FAQ d'Intégration](#-faq-dintégration)

---

## 🔷 Projets Laravel

### ✅ Prérequis

- Laravel 9.0+
- PHP 8.1+
- Composer

### 📍 Installation (5 min)

**Étape 1 : Installer via Composer**

```bash
composer require dontka/all-php-payment-gateway-manager
```

**Étape 2 : Enregistrer le Service Provider** (auto-découverte Laravel 5.5+)

```php
// config/app.php (optionnel si auto-découverte)
'providers' => [
    // ...
    PaymentGateway\ServiceProvider::class,
],

'aliases' => [
    // ...
    'Payment' => PaymentGateway\Facades\Payment::class,
],
```

**Étape 3 : Publier les ressources**

```bash
php artisan vendor:publish --provider="PaymentGateway\ServiceProvider"
```

Cela crée :
- `config/payment.php` - Configuration
- `database/migrations/` - Tables de paiement
- `routes/payment.php` - Routes webhooks

**Étape 4 : Migrer la base de données**

```bash
php artisan migrate
```

**Étape 5 : Configurer les variables d'environnement**

```bash
# .env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_sandbox_client_id
PAYPAL_CLIENT_SECRET=your_sandbox_secret

STRIPE_API_KEY=sk_test_...
STRIPE_SECRET_KEY=sk_test_...
```

### 🚀 Premier Paiement

**Créer une commande et accepter le paiement**

```php
// app/Http/Controllers/OrderController.php
namespace App\Http\Controllers;

use App\Models\Order;
use PaymentGateway\Facades\Payment;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        // 1. Créer la commande
        $order = Order::create([
            'user_id' => auth()->id(),
            'amount' => 99.99,
            'currency' => 'EUR',
            'status' => 'pending'
        ]);

        // 2. Traiter le paiement
        $payment = Payment::charge([
            'gateway' => 'paypal', // ou 'stripe'
            'amount' => 99.99,
            'currency' => 'EUR',
            'customer' => [
                'email' => auth()->user()->email,
                'name' => auth()->user()->name
            ],
            'metadata' => ['order_id' => $order->id]
        ]);

        if ($payment['success']) {
            // 3. Sauvegarder l'ID de transaction
            $order->update([
                'transaction_id' => $payment['transaction_id'],
                'status' => 'processing'
            ]);

            return redirect($payment['approval_link'] ?? '/payment-success');
        }

        return back()->with('error', 'Erreur de paiement');
    }
}
```

**Configurer les routes**

```php
// routes/web.php
Route::post('/checkout', [OrderController::class, 'checkout'])->middleware('auth');
Route::get('/payment-success', [OrderController::class, 'success']);
Route::post('/webhooks/payment', [PaymentWebhookController::class, 'handle'])->withoutMiddleware('verify_csrf_token');
```

**Gérer les webhooks**

```php
// app/Http/Controllers/PaymentWebhookController.php
namespace App\Http\Controllers;

use PaymentGateway\Facades\Payment;
use App\Models\Order;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Déterminer le gateway
        $gateway = $request->get('gateway') ?? 'paypal';

        // Traiter le webhook
        $result = Payment::gateway($gateway)->handleWebhook(
            $request->all(),
            $request->headers->all()
        );

        if (!$result['success']) {
            Log::error('Webhook failed', $result);
            return response()->json(['error' => 'Failed'], 400);
        }

        // Mettre à jour la commande
        $order = Order::where('transaction_id', $result['transaction_id'])
            ->firstOrFail();

        $order->update(['status' => $result['status']]);

        // Déclencher les actions
        if ($result['status'] === 'completed') {
            // Activer la commande
            $order->markAsCompleted();
            
            // Envoyer email
            Mail::send('emails.order-confirmed', compact('order'));
        }

        return response()->json(['status' => 'ok']);
    }
}
```

---

## 🔹 Projets Symfony

### ✅ Prérequis

- Symfony 5.0+
- PHP 8.1+
- Composer

### 📍 Installation (5 min)

**Étape 1 : Installer les dépendances**

```bash
composer require dontka/all-php-payment-gateway-manager
composer require symfony/http-client symfony/dependency-injection
```

**Étape 2 : Configurer le service**

```yaml
# config/services.yaml
services:
  payment.manager:
    class: PaymentGateway\Core\PaymentManager
    arguments:
      paypal_client_id: '%env(PAYPAL_CLIENT_ID)%'
      paypal_secret: '%env(PAYPAL_CLIENT_SECRET)%'
      stripe_api_key: '%env(STRIPE_API_KEY)%'

  payment_gateway.paypal:
    class: PaymentGateway\Gateways\PayPalGateway
    arguments:
      - '@payment.manager'

  payment_gateway.stripe:
    class: PaymentGateway\Gateways\StripeGateway
    arguments:
      - '@payment.manager'
```

**Étape 3 : Variables d'environnement**

```bash
# .env.local
PAYPAL_CLIENT_ID=your_sandbox_client_id
PAYPAL_CLIENT_SECRET=your_sandbox_secret
STRIPE_API_KEY=sk_test_...
```

### 🚀 Utilisation dans un Contrôleur

```php
// src/Controller/PaymentController.php
namespace App\Controller;

use PaymentGateway\Core\PaymentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController
{
    public function __construct(private PaymentManager $paymentManager) {}

    public function checkout(Request $request): Response
    {
        // Créer le paiement
        $result = $this->paymentManager->charge([
            'gateway' => 'stripe',
            'amount' => 99.99,
            'currency' => 'EUR',
            'customer' => [
                'email' => $request->get('email'),
                'name' => $request->get('name')
            ]
        ]);

        if ($result['success']) {
            return new Response('✅ Paiement réussi: ' . $result['transaction_id']);
        }

        return new Response('❌ Erreur: ' . $result['error'], 400);
    }

    public function webhook(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        $result = $this->paymentManager->handleWebhook(
            $payload,
            $request->headers->all()
        );

        return new Response(json_encode($result));
    }
}
```

**Configurer les routes**

```yaml
# config/routes.yaml
payment_checkout:
  path: /checkout
  controller: App\Controller\PaymentController::checkout
  methods: POST

payment_webhook:
  path: /webhook/payment
  controller: App\Controller\PaymentController::webhook
  methods: POST
```

---

## 🔸 PHP Vanilla

Pour utiliser dans un projet PHP sans framework.

### ✅ Prérequis

- PHP 8.1+
- Composer
- MySQL/PostgreSQL (optionnel, pour persistence)

### 📍 Installation (3 min)

**Étape 1 : Installer via Composer**

```bash
composer require dontka/all-php-payment-gateway-manager
```

**Étape 2 : Charger l'autoloader**

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use PaymentGateway\Core\PaymentManager;
use PaymentGateway\Gateways\PayPalGateway;
```

**Étape 3 : Configuration**

```php
// config.php
return [
    'paypal' => [
        'client_id' => getenv('PAYPAL_CLIENT_ID'),
        'secret' => getenv('PAYPAL_CLIENT_SECRET'),
        'mode' => 'sandbox'
    ],
    'stripe' => [
        'api_key' => getenv('STRIPE_API_KEY'),
        'secret' => getenv('STRIPE_SECRET_KEY')
    ]
];
```

### 🚀 Premier Paiement

**Script simple pour traiter un paiement**

```php
<?php
require_once 'vendor/autoload.php';

use PaymentGateway\Core\PaymentManager;
use PaymentGateway\Gateways\PayPalGateway;

$config = require 'config.php';

// 1. Initialiser le payment manager
$paymentManager = new PaymentManager();

// 2. Enregistrer les gateways
$paypalGateway = new PayPalGateway(
    apiKey: $config['paypal']['client_id'],
    secret: $config['paypal']['secret'],
    mode: $config['paypal']['mode']
);

$paymentManager->registerGateway('paypal', $paypalGateway);

// 3. Traiter un paiement
$result = $paymentManager->charge([
    'gateway' => 'paypal',
    'amount' => 99.99,
    'currency' => 'USD',
    'customer' => [
        'email' => 'client@example.com',
        'name' => 'Jean Dupont'
    ]
]);

// 4. Gérer le résultat
if ($result['success']) {
    echo "✅ Paiement créé!\n";
    echo "Order ID: " . $result['order_id'] . "\n";
    echo "Approval Link: " . $result['approval_link'] . "\n";
    
    // Rediriger le client
    header('Location: ' . $result['approval_link']);
} else {
    echo "❌ Erreur: " . $result['error'] . "\n";
}
?>
```

**Fichier webhook**

```php
<?php
// webhook.php
require_once 'vendor/autoload.php';

use PaymentGateway\Core\PaymentManager;

$config = require 'config.php';
$paymentManager = new PaymentManager();

// Récupérer la payload
$payload = json_decode(file_get_contents('php://input'), true);
$headers = getallheaders();

// Traiter le webhook
$result = $paymentManager->handleWebhook($payload, $headers);

if ($result['success']) {
    // Sauvegarder en base de données
    savePaymentToDB([
        'transaction_id' => $result['transaction_id'],
        'status' => $result['status'],
        'amount' => $result['amount'],
        'gateway' => 'paypal',
        'received_at' => date('Y-m-d H:i:s')
    ]);
    
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
} else {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
}

function savePaymentToDB($data) {
    // Implémentation de sauvegarde en BDD
    // Exemple avec PDO :
    // $pdo->prepare('INSERT INTO payments ...')
    //     ->execute($data);
}
?>
```

---

## 🔴 WordPress

Pour utiliser dans une boutique WooCommerce.

### ✅ Prérequis

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 8.1+

### 📍 Installation

**Étape 1 : Créer le plugin**

```bash
mkdir wp-content/plugins/payment-gateway-manager
cd wp-content/plugins/payment-gateway-manager
```

**Étape 2 : Fichier principal du plugin**

```php
<?php
/**
 * Plugin Name: Payment Gateway Manager
 * Plugin URI: https://github.com/dontka/all-php-payment-gateway-manager
 * Description: Intégration PayPal, Stripe et autres pour WooCommerce
 * Version: 1.0.0
 * Author: Your Company
 * License: MIT
 */

if (!defined('ABSPATH')) {
    exit;
}

// Autoloader Composer
require_once __DIR__ . '/vendor/autoload.php';

use PaymentGateway\Core\PaymentManager;
use PaymentGateway\Gateways\PayPalGateway;

class PaymentGatewayManager {
    private $manager;

    public function __construct() {
        add_action('woocommerce_init', [$this, 'initPaymentMethods']);
        add_action('rest_api_init', [$this, 'registerWebhooks']);
    }

    public function initPaymentMethods() {
        $this->manager = new PaymentManager();
        
        // Enregistrer PayPal
        add_filter('woocommerce_payment_gateways', [$this, 'addGateway']);
    }

    public function addGateway($gateways) {
        $gateways[] = 'PayPal_Gateway';
        $gateways[] = 'Stripe_Gateway';
        return $gateways;
    }

    public function registerWebhooks() {
        register_rest_route('payment/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handleWebhook'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function handleWebhook($request) {
        $payload = $request->get_json_params();
        
        $result = $this->manager->handleWebhook($payload);

        // Trouver la commande WooCommerce
        $orders = wc_get_orders([
            'meta_key' => '_payment_transaction_id',
            'meta_value' => $result['transaction_id']
        ]);

        if ($orders) {
            $order = $orders[0];
            
            if ($result['status'] === 'completed') {
                $order->payment_complete($result['transaction_id']);
                $order->add_order_note('Paiement géré par Payment Gateway Manager');
            } else if ($result['status'] === 'failed') {
                $order->update_status('failed', 'Paiement échoué');
            }
        }

        return new \WP_REST_Response(['status' => 'ok'], 200);
    }
}

// Démarrer le plugin
new PaymentGatewayManager();
?>
```

---

## ❓ FAQ d'Intégration

### Q1: Quel framework utiliser ?

**R:** Tous sont supportés !
- ✅ **Laravel** : Support natif (recommandé)
- ✅ **Symfony** : Via configuration services
- ✅ **PHP Vanilla** : Compatible sans framework
- ✅ **WordPress** : Via plugin WooCommerce

### Q2: Comment récupérer les clés API ?

**PayPal :**
1. Aller sur [developer.paypal.com](https://developer.paypal.com)
2. Se connecter
3. Aller dans **Apps & Credentials**
4. Copier Client ID et Secret

**Stripe :**
1. Aller sur [dashboard.stripe.com](https://dashboard.stripe.com)
2. Se connecter
3. Aller dans **Settings → API Keys**
4. Copier la clé secrète

### Q3: Comment tester localement avec les webhooks ?

**Utiliser ngrok :**
```bash
# Démarrer ngrok
ngrok http 8000

# Configurer l'URL webhooks dans PayPal/Stripe
https://xxx.ngrok.io/webhooks/payment
```

### Q4: Comment gérer les erreurs de paiement ?

```php
try {
    $result = Payment::charge([...]);
} catch (ValidationException $e) {
    echo "Erreur de validation: " . $e->getField();
} catch (PaymentException $e) {
    echo "Erreur de paiement: " . $e->getMessage();
} catch (Exception $e) {
    echo "Erreur serveur: " . $e->getMessage();
}
```

### Q5: La sécurité est-elle garantie ?

✅ **Oui, plusieurs couches :**
- Chiffrement AES-256 des clés API
- Vérification de signature des webhooks
- Validation complète des données
- Audit trail complet
- Support HTTPS obligatoire

### Q6: Comment supporter plusieurs devises ?

```php
$payment = Payment::gateway('paypal')->charge([
    'amount' => 100,
    'currency' => 'EUR' // Supporte 20+ devises
]);

// Taux de change géré automatiquement
```

### Q7: Performances ?

✅ **Optimisé :**
- Temps moyen : 200-300ms
- Cache des tokens d'accès
- Logs asynchrones
- Base de données indexée

---

## 📞 Besoin d'Aide ?

- 📖 [Documentation Complète](../docs/)
- 🐛 [GitHub Issues](https://github.com/dontka/all-php-payment-gateway-manager/issues)
- 💬 [Discussions](https://github.com/dontka/all-php-payment-gateway-manager/discussions)
- 📧 support@example.com

