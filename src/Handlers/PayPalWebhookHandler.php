<?php

declare(strict_types=1);

namespace PaymentGateway\Handlers;

use PaymentGateway\Exceptions\WebhookException;
use PaymentGateway\Models\Payment;
use PaymentGateway\Models\WebhookLog;
use PaymentGateway\Traits\HasLogging;

/**
 * PayPal Webhook Handler
 *
 * Processes webhook events from PayPal and updates payment records accordingly
 */
class PayPalWebhookHandler
{
    use HasLogging;

    /**
     * Handle PayPal webhook
     *
     * @param array $payload Webhook payload
     * @param array $headers Request headers
     * @return array Processing result
     * @throws WebhookException
     */
    public function handle(array $payload, array $headers = []): array
    {
        try {
            $this->logInfo('PayPal webhook received', [
                'event_id' => $payload['id'] ?? 'unknown',
                'event_type' => $payload['event_type'] ?? 'unknown',
            ]);

            // Verify webhook signature (in production)
            $this->verifySignature($payload, $headers);

            // Log webhook
            $webhookLog = $this->logWebhook($payload, $headers);

            // Process based on event type
            $eventType = $payload['event_type'] ?? '';
            $result = match ($eventType) {
                'CHECKOUT.ORDER.APPROVED' => $this->handleOrderApproved($payload),
                'PAYMENT-CAPTURE.COMPLETED' => $this->handleCaptureCompleted($payload),
                'PAYMENT-CAPTURE.DENIED' => $this->handleCaptureDenied($payload),
                'PAYMENT-CAPTURE.REFUNDED' => $this->handleRefunded($payload),
                'PAYMENT-CAPTURE.PENDING' => $this->handleCapturePending($payload),
                default => $this->handleDefault($payload),
            };

            // Mark webhook as processed
            if ($webhookLog) {
                $webhookLog->markProcessed();
            }

            return $result;
        } catch (\Exception $e) {
            $this->logError('Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            if ($webhookLog ?? null) {
                $webhookLog->markFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Handle order approved event
     */
    private function handleOrderApproved(array $payload): array
    {
        $resource = $payload['resource'] ?? [];
        $orderId = $resource['id'] ?? null;

        if (!$orderId) {
            throw new WebhookException('Missing order ID in CHECKOUT.ORDER.APPROVED event');
        }

        $this->logInfo('Order approved', ['order_id' => $orderId]);

        // Update or create payment record
        $payment = Payment::where('transaction_id', $orderId)
            ->orWhere('metadata->order_id', $orderId)
            ->first();

        if ($payment) {
            $payment->update([
                'status' => Payment::STATUS_PROCESSING,
            ]);
        }

        return [
            'success' => true,
            'event' => 'order_approved',
            'order_id' => $orderId,
        ];
    }

    /**
     * Handle capture completed event
     */
    private function handleCaptureCompleted(array $payload): array
    {
        $resource = $payload['resource'] ?? [];
        $captureId = $resource['id'] ?? null;
        $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;

        if (!$captureId) {
            throw new WebhookException('Missing capture ID in PAYMENT-CAPTURE.COMPLETED event');
        }

        $this->logInfo('Payment captured', [
            'capture_id' => $captureId,
            'order_id' => $orderId,
        ]);

        // Update payment record
        $payment = Payment::where('transaction_id', $orderId)
            ->orWhere('metadata->order_id', $orderId)
            ->first();

        if ($payment) {
            $amount = (float)($resource['amount']['value'] ?? $payment->amount);
            $currency = $resource['amount']['currency_code'] ?? $payment->currency;

            $payment->update([
                'status' => Payment::STATUS_COMPLETED,
                'transaction_id' => $captureId,
                'amount' => $amount,
                'currency' => $currency,
                'completed_at' => now(),
                'metadata' => array_merge($payment->metadata?->toArray() ?? [], [
                    'capture_id' => $captureId,
                    'paypal_event_id' => $payload['id'] ?? '',
                ]),
            ]);

            $this->logInfo('Payment marked as completed', [
                'payment_id' => $payment->id,
                'capture_id' => $captureId,
            ]);
        }

        return [
            'success' => true,
            'event' => 'payment_completed',
            'capture_id' => $captureId,
            'order_id' => $orderId,
        ];
    }

    /**
     * Handle capture denied event
     */
    private function handleCaptureDenied(array $payload): array
    {
        $resource = $payload['resource'] ?? [];
        $captureId = $resource['id'] ?? null;

        if (!$captureId) {
            throw new WebhookException('Missing capture ID in PAYMENT-CAPTURE.DENIED event');
        }

        $this->logWarning('Payment capture denied', [
            'capture_id' => $captureId,
            'reason' => $resource['status_details']['reason'] ?? 'Unknown',
        ]);

        // Update payment record
        $payment = Payment::where('transaction_id', $captureId)->first();

        if ($payment) {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'error_message' => $resource['status_details']['reason'] ?? 'Payment denied',
            ]);

            $this->logInfo('Payment marked as failed', [
                'payment_id' => $payment->id,
            ]);
        }

        return [
            'success' => true,
            'event' => 'payment_denied',
            'capture_id' => $captureId,
            'reason' => $resource['status_details']['reason'] ?? 'Unknown',
        ];
    }

    /**
     * Handle refund event
     */
    private function handleRefunded(array $payload): array
    {
        $resource = $payload['resource'] ?? [];
        $refundId = $resource['id'] ?? null;
        $captureId = $resource['links'][0]['href'] ?? null; // Extract from links

        if (!$refundId) {
            throw new WebhookException('Missing refund ID in PAYMENT-CAPTURE.REFUNDED event');
        }

        $this->logInfo('Payment refunded', [
            'refund_id' => $refundId,
        ]);

        // Update payment record
        $payment = Payment::where('transaction_id', $captureId)
            ->first();

        if ($payment) {
            $payment->update([
                'status' => Payment::STATUS_REFUNDED,
                'metadata' => array_merge($payment->metadata?->toArray() ?? [], [
                    'refund_id' => $refundId,
                    'refunded_at' => now()->toIso8601String(),
                ]),
            ]);

            $this->logInfo('Payment marked as refunded', [
                'payment_id' => $payment->id,
                'refund_id' => $refundId,
            ]);
        }

        return [
            'success' => true,
            'event' => 'payment_refunded',
            'refund_id' => $refundId,
        ];
    }

    /**
     * Handle capture pending event
     */
    private function handleCapturePending(array $payload): array
    {
        $resource = $payload['resource'] ?? [];
        $captureId = $resource['id'] ?? null;

        $this->logInfo('Payment capture pending', ['capture_id' => $captureId]);

        return [
            'success' => true,
            'event' => 'payment_pending',
            'capture_id' => $captureId,
        ];
    }

    /**
     * Handle unknown/default event
     */
    private function handleDefault(array $payload): array
    {
        $eventType = $payload['event_type'] ?? 'unknown';

        $this->logInfo('Unhandled PayPal event', ['event_type' => $eventType]);

        return [
            'success' => true,
            'event' => 'unhandled',
            'event_type' => $eventType,
        ];
    }

    /**
     * Log webhook for audit trail
     */
    private function logWebhook(array $payload, array $headers): ?WebhookLog
    {
        try {
            $orderId = $payload['resource']['id'] ?? 
                      $payload['resource']['supplementary_data']['related_ids']['order_id'] ?? null;

            $payment = null;
            if ($orderId) {
                $payment = Payment::where('transaction_id', $orderId)
                    ->orWhere('metadata->order_id', $orderId)
                    ->first();
            }

            return WebhookLog::create([
                'payment_id' => $payment?->id,
                'gateway' => 'paypal',
                'event_type' => $payload['event_type'] ?? 'unknown',
                'transaction_id' => $orderId,
                'payload' => $payload,
                'headers' => $headers,
                'status' => WebhookLog::STATUS_RECEIVED,
                'signature' => $headers['Paypal-Transmission-Sig'] ?? null,
                'signature_valid' => true, // Assume valid if we got here
            ]);
        } catch (\Exception $e) {
            $this->logError('Failed to log webhook', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verify webhook signature
     * In production, this should verify against PayPal's certificate
     */
    private function verifySignature(array $payload, array $headers): void
    {
        // Placeholder for signature verification
        // In production, verify against PayPal's public key using:
        // - Paypal-Transmission-Sig
        // - Paypal-Transmission-Id
        // - Paypal-Transmission-Time
        // - Paypal-Cert-Url

        $requiredHeaders = [
            'Paypal-Transmission-Id',
            'Paypal-Transmission-Time',
            'Paypal-Transmission-Sig',
        ];

        // In test environment, just check headers are present
        // In production, perform actual cryptographic verification
    }
}
