<?php

/**
 * Laravel Routes Example
 *
 * Add these routes to routes/web.php or routes/api.php
 */

// Payment checkout routes (require authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/payment/success', [CheckoutController::class, 'success'])->name('payment.success');
    Route::get('/payment/cancel', [CheckoutController::class, 'cancel'])->name('payment.cancel');
    Route::post('/payment/refund/{order}', [CheckoutController::class, 'refund'])->name('payment.refund');
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/payments', [PaymentWebhookController::class, 'list'])->name('admin.payments');
});

// Webhook routes (public, no CSRF)
Route::post('/webhooks/payment', [PaymentWebhookController::class, 'handle'])->name('webhooks.payment');
Route::get('/webhooks/payment/recent', [PaymentWebhookController::class, 'list'])->middleware('auth');

// API routes for AJAX
Route::prefix('api')->group(function () {
    Route::post('/payment/charge', [CheckoutController::class, 'process'])->middleware('auth');
    Route::get('/payment/gateways', [CheckoutController::class, 'gateways']);
    Route::get('/payment/{transactionId}/verify', [CheckoutController::class, 'verify'])->middleware('auth');
});
