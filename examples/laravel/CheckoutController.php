<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use PaymentGateway\Facades\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Laravel Checkout Controller Example
 *
 * Handles payment processing for e-commerce orders
 *
 * Usage:
 *   POST /checkout
 *   Body: { amount: 99.99, currency: 'EUR' }
 */
class CheckoutController extends Controller
{
    /**
     * Show checkout form
     */
    public function show()
    {
        return view('checkout.form');
    }

    /**
     * Process payment
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function process(Request $request)
    {
        try {
            // 1. Validate input
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'required|in:USD,EUR,GBP,JPY',
                'customer_email' => 'required|email',
                'gateway' => 'nullable|in:paypal,stripe'
            ]);

            $gateway = $validated['gateway'] ?? 'paypal';

            // 2. Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'status' => 'pending',
                'metadata' => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]
            ]);

            Log::info('Order created', ['order_id' => $order->id]);

            // 3. Process payment with gateway
            $payment = Payment::gateway($gateway)->charge([
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'customer' => [
                    'email' => $validated['customer_email'],
                    'name' => auth()->user()->name
                ],
                'description' => "Order #{$order->id}",
                'metadata' => ['order_id' => $order->id]
            ]);

            if ($payment['success']) {
                // 4. Save transaction
                $order->update([
                    'transaction_id' => $payment['transaction_id'] ?? $payment['order_id'] ?? null,
                    'gateway' => $gateway,
                    'status' => 'processing'
                ]);

                Log::info('Payment initiated', [
                    'order_id' => $order->id,
                    'gateway' => $gateway,
                    'amount' => $validated['amount']
                ]);

                // 5. Redirect to payment page (especially for PayPal)
                if (isset($payment['approval_link'])) {
                    return redirect($payment['approval_link']);
                }

                // For direct payment (Stripe), redirect to success
                return redirect()->route('payment.success', $order);
            }

            // Payment failed
            Log::error('Payment failed', [
                'order_id' => $order->id,
                'error' => $payment['error'] ?? 'Unknown error'
            ]);

            return back()->with('error', 'Payment failed: ' . ($payment['error'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('Checkout error', ['message' => $e->getMessage()]);
            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Payment success page (called after return from PayPal)
     *
     * @param Order $order
     * @return \Illuminate\View\View
     */
    public function success(Order $order)
    {
        // If PayPal, need to capture the order
        if ($order->gateway === 'paypal' && $order->status === 'processing') {
            $orderId = request()->get('orderId');

            if ($orderId) {
                $captured = Payment::gateway('paypal')
                    ->captureOrderPayment($orderId);

                if ($captured['success']) {
                    $order->update([
                        'transaction_id' => $captured['capture_id'],
                        'status' => 'completed',
                        'completed_at' => now()
                    ]);

                    // Send confirmation email
                    Mail::send('emails.payment-confirmed', ['order' => $order]);

                    // Fire event for listeners
                    event(new \PaymentGateway\Events\PaymentSuccessEvent([
                        'transaction_id' => $captured['capture_id'],
                        'amount' => $order->amount,
                        'order_id' => $order->id
                    ]));
                }
            }
        }

        return view('checkout.success', compact('order'));
    }

    /**
     * Payment cancelled
     */
    public function cancel()
    {
        return view('checkout.cancelled')->with('error', 'Payment cancelled.');
    }

    /**
     * Get available gateways
     */
    public function gateways()
    {
        return response()->json([
            'gateways' => [
                'paypal' => 'PayPal',
                'stripe' => 'Stripe'
            ]
        ]);
    }

    /**
     * Refund an order
     *
     * @param Order $order
     * @param Request $request
     */
    public function refund(Order $order, Request $request)
    {
        try {
            if ($order->status !== 'completed') {
                return back()->with('error', 'Only completed payments can be refunded.');
            }

            $amount = $request->get('amount', $order->amount);
            $reason = $request->get('reason', 'Customer request');

            $refund = Payment::gateway($order->gateway)->refund(
                $order->transaction_id,
                $amount,
                ['reason' => $reason]
            );

            if ($refund['success']) {
                $order->update([
                    'status' => 'refunded',
                    'refund_id' => $refund['refund_id'],
                    'refunded_at' => now(),
                    'refund_amount' => $amount
                ]);

                Log::info('Refund processed', [
                    'order_id' => $order->id,
                    'refund_id' => $refund['refund_id'],
                    'amount' => $amount
                ]);

                Mail::send('emails.refund-processed', ['order' => $order]);

                return back()->with('success', 'Refund processed successfully.');
            }

            return back()->with('error', 'Refund failed: ' . ($refund['error'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('Refund error', ['message' => $e->getMessage()]);
            return back()->with('error', 'Refund error: ' . $e->getMessage());
        }
    }
}
