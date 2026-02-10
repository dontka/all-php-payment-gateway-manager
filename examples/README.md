# 📁 Examples - Exemples Concrets d'Intégration

Ce dossier contient des exemples complets et prêts à utiliser pour intégrer le Payment Gateway Manager dans votre projet.

## 📂 Structure

```
examples/
├── laravel/
│   ├── CheckoutController.php
│   ├── PaymentWebhookController.php
│   ├── routes.php
│   └── OrderModel.php
├── symfony/
│   ├── PaymentController.php
│   ├── WebhookController.php
│   ├── services.yaml
│   └── routes.yaml
├── php-vanilla/
│   ├── checkout.php
│   ├── webhook.php
│   └── config.php
├── wordpress/
│   ├── plugin.php
│   └── woocommerce-integration.php
└── README.md
```

## 🚀 Quick Start

### Laravel
```bash
# Copier les fichiers
cp -r examples/laravel/* app/

# Configuration
php artisan migrate
# Ajouter variables .env
```

### Symfony
```bash
# Copier les fichiers
cp -r examples/symfony/* src/

# Configuration
cp examples/symfony/services.yaml config/packages/
```

### PHP Vanilla
```bash
# Copier et utiliser directement
php examples/php-vanilla/checkout.php
```

### WordPress
```bash
# Copier dans plugins
cp -r examples/wordpress wp-content/plugins/payment-gateway

# Activer dans WordPress
```

---

## 📖 Chaque Exemple Inclut

✅ Code complet et fonctionnel
✅ Configuration inline
✅ Gestion d'erreurs
✅ Webhooks configurés
✅ Base de données intégrée
✅ Commentaires détaillés
✅ Ready for production avec ajustements

---

## 🔗 Documentation Complémentaire

- [Integration Guide](../docs/INTEGRATION_GUIDE.md) - Guide détaillé
- [Quick Start](../QUICK_START.md) - Démarrage rapide
- [API Reference](../docs/API.md) - Référence API

