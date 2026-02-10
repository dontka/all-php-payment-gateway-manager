# 🚀 GUIDE DE DÉMARRAGE RAPIDE

## ⚡ 5 Minutes Setup

### Prérequis
```bash
- PHP 8.1+
- Composer
- Git
- Docker (optionnel)
```

### Installation Basique

```bash
# 1. Cloner le repo
git clone https://github.com/dontka/all-all-php-payment-gateway-manager-manager.git
cd all-php-payment-gateway-manager

# 2. Installer les dépendances
composer install

# 3. Copier la configuration
cp .env.example .env

# 4. Générer la clé travail
php artisan key:generate

# 5. Lancer le serveur
php artisan serve
```

**Terminé !** → http://localhost:8000

---

## 🔑 Configuration Rapide

### Stripe

```bash
# 1. Créer un compte https://stripe.com
# 2. Récupérer les clés Test
# 3. Mettre à jour .env

STRIPE_API_KEY=sk_test_YOUR_KEY_HERE
STRIPE_SECRET_KEY=sk_test_YOUR_SECRET_HERE
```

### PayPal

```bash
# 1. Créer un compte https://paypal.com
# 2. Aller dans Developer Dashboard
# 3. Créer une application
# 4. Mettre à jour .env

PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=YOUR_CLIENT_ID
PAYPAL_CLIENT_SECRET=YOUR_CLIENT_SECRET
```

### Flutterwave

```bash
# 1. Créer un compte https://flutterwave.com
# 2. Aller dans Settings → API
# 3. Copier les clés
# 4. Mettre à jour .env

FLUTTERWAVE_PUBLIC_KEY=FLWPUBK_TEST_YOUR_KEY
FLUTTERWAVE_SECRET_KEY=FLWSECK_TEST_YOUR_SECRET
```

---

## 💡 Premier Paiement

### Code PHP Simple

```php
<?php

// 1. Initialiser le gestionnaire
use PaymentGateway\Facades\Payment;

// 2. Effectuer un paiement
$result = Payment::charge([
    'amount' => 100.00,
    'currency' => 'USD',
    'source' => 'tok_visa',
    'description' => 'Test payment',
    'customer_id' => 'customer_123'
], 'stripe');

// 3. Vérifier le résultat
if ($result['success']) {
    echo "✅ Payment successful!";
    echo "Transaction ID: " . $result['transaction_id'];
} else {
    echo "❌ Payment failed!";
    echo "Error: " . $result['error'];
}
```

### Avec Laravel

```php
<?php

namespace App\Http\Controllers;

use PaymentGateway\Facades\Payment;

class CheckoutController extends Controller
{
    public function process(Request $request)
    {
        // Valider la requête
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,EUR,GBP',
            'customer_token' => 'required|string'
        ]);

        // Effectuer le paiement
        try {
            $result = Payment::charge([
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'source' => $validated['customer_token'],
                'description' => 'Product purchase'
            ]);

            if ($result['success']) {
                // Enregistrer la transaction
                transaction()->create([
                    'gateway' => 'stripe',
                    'transaction_id' => $result['transaction_id'],
                    'amount' => $validated['amount'],
                    'status' => 'succeeded'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful',
                    'transaction_id' => $result['transaction_id']
                ]);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 400);
        }
    }
}
```

### Avec Plain PHP

```php
<?php

require_once 'vendor/autoload.php';

use PaymentGateway\Core\PaymentManager;
use PaymentGateway\Gateways\StripeGateway;

// Configuration
$config = [
    'default_gateway' => 'stripe',
    'gateways' => [
        'stripe' => [
            'class' => StripeGateway::class,
            'api_key' => getenv('STRIPE_API_KEY'),
            'secret_key' => getenv('STRIPE_SECRET_KEY'),
            'test_mode' => true
        ]
    ]
];

// Initialiser
$payment = new PaymentManager($config);

// Charge
$result = $payment->charge([
    'amount' => 99.99,
    'currency' => 'USD',
    'source' => 'tok_visa'
]);

// Résultat
var_dump($result);
```

---

## 🧪 Commandes Utiles

### Testing

```bash
# Tous les tests
php ./vendor/bin/phpunit

# Tests spécifiques
php ./vendor/bin/phpunit tests/Feature/StripeIntegrationTest.php

# Tests avec couverture
php ./vendor/bin/phpunit --coverage-html coverage/

# Watcher (Dev)
php ./vendor/bin/phpunit --testdox --watch
```

### Code Quality

```bash
# Analyser le code
php ./vendor/bin/phpstan analyse src/

# Formater le code
php ./vendor/bin/php-cs-fixer fix

# Lint PHP
php ./vendor/bin/parallel-lint src/ tests/
```

### Database

```bash
# Créer les tables
php artisan migrate

# Annuler les migrations
php artisan migrate:rollback

# Seed de données
php artisan db:seed
```

### Maintenance

```bash
# Cache clearing
php artisan cache:clear
php artisan config:cache
php artisan view:clear
php artisan route:cache

# Logs
tail -f storage/logs/laravel.log
```

---

## 🛠️ CLI Installation

### Installation Guidée

```bash
php artisan payment:install

# Questions interactives:
# 1. Quels gateways installer?
# 2. Entrer les clés API pour chaque gateway
# 3. Exécuter les migrations? (Oui/Non)
# 4. Charger les données de test? (Oui/Non)
```

### Configuration Manuelle

```bash
# Setup spécifique pour Stripe
php artisan payment:setup stripe

# Setup spécifique pour PayPal
php artisan payment:setup paypal

# Tester un gateway
php artisan payment:test --gateway=stripe --amount=10 --currency=USD
```

---

## 📊 Dashboard d'Administration

### Accès

```
URL: http://localhost:8000/admin/payments
Username: admin@example.com
Password: (voir .env)
```

### Fonctionnalités

**Dashboard principal:**
- Statistiques en temps réel
- Transactions récentes
- Revenue chart
- Health status

**Transactions:**
- Liste complète
- Recherche avancée
- Filtrage par gateway/statut
- Export CSV/PDF
- Détails transaction

**Settings:**
- Gestion des clés API
- Configuration des webhooks
- Email notifications
- Security settings

**Logs:**
- Webhooks reçus
- Erreurs système
- Activity audit
- Export logs

---

## 🔗 Webhooks Setup

### Recevoir les Events

#### Stripe

```bash
# Tester localement avec ngrok
ngrok http 8000

# Configurer dans Stripe Dashboard
Developers → Webhooks
URL: https://your-ngrok-url.ngrok.io/webhooks/stripe

# Events à activer:
- charge.succeeded
- charge.failed
- charge.refunded
- charge.dispute.created
```

#### PayPal

```bash
# Dashboard PayPal
Apps & Credentials → Webhooks (Sandbox)
URL: http://your-domain.com/webhooks/paypal

# Events:
- PAYMENT.SALE.COMPLETED
- PAYMENT.SALE.DENIED
- PAYMENT.SALE.REFUNDED
```

### Handler Webhook

```php
<?php

namespace App\Http\Controllers;

use PaymentGateway\Events\PaymentSuccessEvent;
use PaymentGateway\Events\PaymentFailedEvent;

class WebhookController extends Controller
{
    public function stripe()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        
        // Vérifier la signature
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        
        // Traiter l'événement
        event(new PaymentSuccessEvent($payload['data'], 'stripe'));
        
        return response()->json(['status' => 'received']);
    }

    public function paypal()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        
        // Vérifier avec PayPal
        // ...
        
        return response()->json(['status' => 'received']);
    }
}
```

### Listeners

```php
<?php

namespace App\Listeners;

use PaymentGateway\Events\PaymentSuccessEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentConfirmation implements ShouldQueue
{
    public function handle(PaymentSuccessEvent $event)
    {
        // Envoyer email de confirmation
        Mail::send('emails.payment-confirmed', $event->toArray());
        
        // Updater le statut de la commande
        Order::where('transaction_id', $event->transaction_id)
            ->update(['status' => 'paid']);
        
        // Logger l'événement
        Log::info('Payment confirmed', $event->toArray());
    }
}
```

---

## 🐛 Dépannage Rapide

### Erreur: "Missing API Key"

```
❓ Problème: Clé API non configurée
✅ Solution:
   1. cp .env.example .env
   2. Ajouter vos clés API
   3. php artisan cache:clear
   4. Relancer le serveur
```

### Erreur: "Webhook signature verification failed"

```
❓ Problème: Signature du webhook invalide
✅ Solution:
   1. Vérifier la WEBHOOK_SECRET dans .env
   2. Vérifier avec le provider (Stripe/PayPal)
   3. Assurez-vous que le timestamp n'a pas expiré
   4. Vérifier les logs pour plus de détails
```

### Erreur: "Connection timeout"

```
❓ Problème: Timeout lors de la connexion à l'API
✅ Solution:
   1. Vérifier votre connexion réseau
   2. Vérifier les limites de taux (rate limits)
   3. Augmenter le timeout dans config/payment.php
   4. Utiliser les logs pour identifier le service
```

### Erreur: "Database connection error"

```
❓ Problème: Impossible de se connecter à la DB
✅ Solution:
   1. Vérifier les credentials .env
   2. php artisan migrate --refresh
   3. Vérifier que MySQL/PostgreSQL tourne
   4. Vérifier les permissions des fichier
