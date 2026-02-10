<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use PaymentGateway\Facades\Payment;
use App\Models\Order;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Laravel Webhook Receiver Example
 *
 * Handles incoming webhooks from PayPal and Stripe
 *
 * Setup:
 *   POST /webhooks/payment
 *   Configure in PayPal/Stripe dashboard
 */
class PaymentWebhookController extends Controller
{
    /**
     * Disable CSRF protection for webhooks
     */
    protected $except = ['handle'];

    /**
     * Handle incoming webhook
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        try {
            // 1. Determine which gateway sent the webhook
            $gateway = $this->detectGateway($request);

            Log::info('Webhook received', [
                'gateway' => $gateway,
                'headers' => $request->headers->all(),
                'path' => $request->path()
            ]);

            // 2. Process webhook with appropriate gateway
            $result = Payment::gateway($gateway)->handleWebhook(
                $request->all(),
                $request->headers->all()
            );

            // 3. Log the webhook event
            WebhookEvent::create([
                'gateway' => $gateway,
                'event_type' => $result['event_type'] ?? 'unknown',
                'transaction_id' => $result['transaction_id'] ?? null,
                'payload' => $request->all(),
                'status' => $result['success'] ? 'processed' : 'failed'
            ]);

            if (!$result['success']) {
                Log::error('Webhook failed', $result);
                return response()->json(['error' => $result['error'] ?? 'Failed'], 400);
            }

            // 4. Find and update the order
            $order = Order::where('transaction_id', $result['transaction_id'])
                ->orWhere('metadata->order_id', $result['transaction_id'])
                ->first();

            if (!$order) {
                Log::warning('Order not found for transaction', [
                    'transaction_id' => $result['transaction_id']
                ]);
                return response()->json(['ok' => true]); // Return 200 anyway
            }

            // 5. Synchronize status with webhook
            $order->update(['status' => $result['status']]);

            Log::info('Order updated via webhook', [
                'order_id' => $order->id,
                'status' => $result['status'],
                'gateway' => $gateway
            ]);

            // 6. Trigger specific actions based on status
            $this->handleStatusChange($order, $result['status']);

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Webhook handler error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Always return 200 to prevent webhook retries on our errors
            return response()->json(['error' => 'Processing error'], 200);
        }
    }

    /**
     * Detect which gateway sent the webhook
     *
     * @param Request $request
     * @return string
     */
    private function detectGateway(Request $request): string
    {
        // Check Stripe signature header
        if ($request->header('Stripe-Signature')) {
            return 'stripe';
        }

        // Check PayPal headers
        if ($request->header('Paypal-Transmission-Id')) {
            return 'paypal';
        }

        // Try to determine from body
        if ($request->has('resource.id')) {
            return 'paypal'; // PayPal uses resource.id
        }

        if ($request->has('id') && str_starts_with($request->get('id'), 'evt_')) {
            return 'stripe'; // Stripe event IDs start with evt_
        }

        return $request->get('gateway', 'paypal');
    }

    /**
     * Handle different payment statuses
     *
     * @param Order $order
     * @param string $status
     */
    private function handleStatusChange(Order $order, string $status): void
    {
        match($status) {
            'completed' => $this->onPaymentCompleted($order),
            'failed' => $this->onPaymentFailed($order),
            'refunded' => $this->onPaymentRefunded($order),
            'cancelled' => $this->onPaymentCancelled($order),
            default => Log::info('Unknown status change', ['status' => $status])
        };
    }

    /**
     * Payment successfully completed
     */
    private function onPaymentCompleted(Order $order): void
    {
        Log::info('Payment completed', ['order_id' => $order->id]);

        // Mark order as completed
        $order->update(['completed_at' => now()]);

        // Send confirmation email
        Mail::send('emails.payment-confirmed', ['order' => $order]);

        // Activate order/product
        $this->activateOrder($order);

        // Dispatch event for listeners
        event(new \PaymentGateway\Events\PaymentSuccessEvent([
            'transaction_id' => $order->transaction_id,
            'order_id' => $order->id,
            'amount' => $order->amount
        ]));
    }

    /**
     * Payment failed
     */
    private function onPaymentFailed(Order $order): void
    {
        Log::warning('Payment failed', ['order_id' => $order->id]);

        // Send failure notification
        Mail::send('emails.payment-failed', ['order' => $order]);

        // Dispatch event
        event(new \PaymentGateway\Events\PaymentFailedEvent([
            'transaction_id' => $order->transaction_id,
            'order_id' => $order->id
        ]));
    }

    /**
     * Payment refunded
     */
    private function onPaymentRefunded(Order $order): void
    {
        Log::info('Payment refunded', ['order_id' => $order->id]);

        // Deactivate order if needed
        $order->update(['refunded_at' => now()]);

        // Send refund notification
        Mail::send('emails.refund-confirmed', ['order' => $order]);
    }

    /**
     * Payment cancelled
     */
    private function onPaymentCancelled(Order $order): void
    {
        Log::info('Payment cancelled', ['order_id' => $order->id]);

        // Send cancellation notification
        Mail::send('emails.payment-cancelled', ['order' => $order]);
    }

    /**
     * Activate order after successful payment
     * (Example: grant access to digital product, etc.)
     */
    private function activateOrder(Order $order): void
    {
        // Example: Mark digital product as available
        if ($order->items) {
            foreach ($order->items as $item) {
                if ($item->is_digital) {
                    $item->update(['activated_at' => now()]);
                }
            }
        }
    }

    /**
     * List recent webhooks (admin only)
     */
    public function list()
    {
        $webhooks = WebhookEvent::latest()->paginate(20);
        return view('webhooks.list', compact('webhooks'));
    }
}
