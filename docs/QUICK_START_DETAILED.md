# ⚡ Quick Start - 5 Minutes

Commencez à accepter les paiements en **moins de 5 minutes**.

---

## 1️⃣ Installation (1 min)

```bash
composer require dontka/all-php-payment-gateway-manager
```

**Seulement 1 ligne !** ✨

---

## 2️⃣ Configuration (2 min)

### 🔷 Laravel

```bash
# Publier la configuration
php artisan vendor:publish --provider="PaymentGateway\ServiceProvider"

# Exécuter les migrations
php artisan migrate
```

```env
# .env
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_secret

STRIPE_API_KEY=sk_test_your_key
```

### 🔹 Symfony / PHP Vanilla

Juste charger l'autoloader :

```php
require_once 'vendor/autoload.php';

use PaymentGateway\Core\PaymentManager;
```

---

## 3️⃣ Votre Premier Paiement (2 min)

### Créer un paiement

```php
use PaymentGateway\Facades\Payment;

$result = Payment::charge([
    'amount' => 99.99,
    'currency' => 'EUR',
    'customer' => ['email' => 'client@example.com'],
    'description' => 'Achat Premium'
]);

if ($result['success']) {
    echo "✅ Paiement réussi!";
    echo "ID: " . $result['transaction_id'];
} else {
    echo "❌ Erreur: " . $result['error'];
}
```

### Accepter spécifiquement PayPal

```php
$result = Payment::gateway('paypal')->charge([
    'amount' => 99.99,
    'currency' => 'EUR',
]);

// Rediriger le client vers PayPal pour approuver
header('Location: ' . $result['approval_link']);
```

### Ou accepter Stripe

```php
$result = Payment::gateway('stripe')->charge([
    'amount' => 99.99,
    'currency' => 'EUR',
    'source' => $request->get('stripe_token') // Token du formulaire
]);
```

---

## 📋 Fichiers Clés à Créer

### 1. Contrôleur des Paiements

```php
// app/Http/Controllers/CheckoutController.php (Laravel)
namespace App\Http\Controllers;

use PaymentGateway\Facades\Payment;
use App\Models\Order;

class CheckoutController extends Controller
{
    public function processPayment(Request $request)
    {
        $order = Order::create([
            'amount' => 99.99,
            'currency' => 'EUR',
            'status' => 'pending'
        ]);

        $payment = Payment::charge([
            'amount' => 99.99,
            'currency' => 'EUR',
            'customer' => ['email' => auth()->user()->email],
        ]);

        if ($payment['success']) {
            $order->update(['transaction_id' => $payment['transaction_id']]);
            return redirect($payment['approval_link']);
        }

        return back()->with('error', 'Erreur de paiement');
    }

    public function success()
    {
        return view('payment.success');
    }
}
```

### 2. Webhook Handler

```php
// app/Http/Controllers/WebhookController.php (Laravel)
namespace App\Http\Controllers;

use PaymentGateway\Facades\Payment;
use App\Models\Order;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $gateway = $request->get('gateway') ?? 'paypal';
        
        // Traiter le webhook
        $result = Payment::gateway($gateway)->handleWebhook(
            $request->all(),
            $request->headers->all()
        );

        if ($result['success']) {
            // Mettre à jour la commande
            Order::where('transaction_id', $result['transaction_id'])
                ->update(['status' => $result['status']]);
            
            return response()->json(['status' => 'ok']);
        }

        return response()->json(['error' => 'Failed'], 400);
    }
}
```

### 3. Routes

```php
// routes/web.php (Laravel)
Route::post('/checkout', [CheckoutController::class, 'processPayment'])->middleware('auth');
Route::get('/payment-success', [CheckoutController::class, 'success']);
Route::post('/webhooks/payment', [WebhookController::class, 'handle'])->withoutMiddleware('csrf');
```

### 4. Modèle

```php
// app/Models/Order.php (Laravel)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['amount', 'currency', 'status', 'transaction_id'];
    
    public function isPaid()
    {
        return $this->status === 'completed';
    }
}
```

---

## 🎯 Utilisation Courantes

### Vérifier le statut d'un paiement

```php
$status = Payment::verify($transactionId);

if ($status['status'] === 'completed') {
    echo "✅ Paiement reçu!";
}
```

### Rembourser un paiement

```php
$refund = Payment::refund($transactionId, 50); // Remboursement partiel

if ($refund['success']) {
    echo "✅ Remboursement traité: " . $refund['refund_id'];
}
```

### Détecter et réagir aux paiements

```php
use PaymentGateway\Events\PaymentSuccessEvent;

Payment::on(PaymentSuccessEvent::class, function($event) {
    // Email de confirmation
    Mail::send('emails.payment-confirmed');
    
    // Activer la commande
    $event->order->markAsActive();
    
    // Logs
    Log::info('Payment received', $event->toArray());
});
```

---

## ✅ Checklist de Mise en Production

- [ ] Clés API configurées en `.env`
- [ ] Migrations exécutées (`php artisan migrate`)
- [ ] URLs de webhook configurées dans PayPal/Stripe
- [ ] Mode production activé (`PAYPAL_MODE=live`)
- [ ] HTTPS activé (obligatoire)
- [ ] Tests avec paiements réels effectués
- [ ] Emails de confirmation envoyés
- [ ] Logs configurés et vérifiés
- [ ] Erreurs gérées correctement
- [ ] Remboursements testés

---

## 🔧 Variables d'Environnement Essentielles

```env
# PayPal
PAYPAL_MODE=sandbox          # ou 'live'
PAYPAL_CLIENT_ID=xxx
PAYPAL_CLIENT_SECRET=xxx

# Stripe
STRIPE_API_KEY=sk_test_xxx
STRIPE_SECRET_KEY=sk_test_xxx

# General
APP_ENV=production           # Important!
APP_URL=https://example.com  # URLs de webhooks
```

---

## 🐛 Debugging

### Voir les logs des paiements

```php
use PaymentGateway\Models\Transaction;

// Tous les paiements échoués
$failures = Transaction::where('status', 'failed')->get();

// Webhooks reçus
$webhooks = \PaymentGateway\Models\WebhookLog::latest()->get();

// Dans les logs Laravel
tail -f storage/logs/laravel.log | grep payment
```

### Tester manuellement un webhook (curl)

```bash
curl -X POST https://example.com/webhooks/payment \
  -H "Content-Type: application/json" \
  -d '{
    "gateway": "paypal",
    "event_type": "PAYMENT-CAPTURE.COMPLETED",
    "transaction_id": "test-123"
  }'
```

---

## 📱 Pages d'Exemple

### Page de Paiement

```html
<!-- resources/views/checkout.blade.php (Laravel) -->
<form action="/checkout" method="POST">
    @csrf
    <input type="hidden" name="amount" value="99.99">
    <input type="hidden" name="currency" value="EUR">
    
    <button type="submit" class="btn btn-primary">
        💳 Payer 99,99 EUR
    </button>
</form>
```

### Page de Succès

```html
<!-- resources/views/payment/success.blade.php -->
<div class="alert alert-success">
    <h1>✅ Paiement Réussi!</h1>
    <p>Merci pour votre commande.</p>
    <p>Un email de confirmation a été envoyé.</p>
</div>
```

---

## 🚀 Prochaines Étapes

1. **Vérifier les tests :** `php artisan test`
2. **Consulter la doc complète :** Voir [docs/INTEGRATION_GUIDE.md](docs/INTEGRATION_GUIDE.md)
3. **Ajouter d'autres gateways :** PayPal ✅, Stripe ✅, Square, Wise, etc.
4. **Personnaliser :** Webhooks, emails, notifications
5. **Sécurité :** HTTPS, validation, rate limiting

---

## 💡 Conseils Pro

✅ **DO:**
- Utiliser des tokens pour les cartes (pas de numéros bruts)
- Vérifier les signatures des webhooks
- Loguer tous les paiements
- Tester en mode sandbox d'abord
- Gérer les erreurs proprement

❌ **DON'T:**
- Stocker les numéros de carte
- Envoyer les API keys en frontside
- Faire confiance aux données du client
- Oublier HTTPS en production
- Négliger les logs

---

## 💬 Besoin d'Aide ?

- 📖 [Guide d'Intégration Complet](docs/INTEGRATION_GUIDE.md)
- 🔌 [Référence API](docs/API.md)
- 🔔 [Documentation Webhooks](docs/WEBHOOKS.md)
- 🐛 [GitHub Issues](https://github.com/dontka/all-php-payment-gateway-manager/issues)

**Vous êtes prêt ! Lancez votre premier paiement maintenant.** 🚀

