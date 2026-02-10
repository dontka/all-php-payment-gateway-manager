<?php

declare(strict_types=1);

namespace App\Controller;

use PaymentGateway\Core\PaymentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Symfony Webhook Controller Example
 *
 * Handles incoming webhooks from PayPal and Stripe
 *
 * Route:
 *   POST /webhook/payment
 */
class WebhookController extends AbstractController
{
    /**
     * Handle payment webhooks
     *
     * @param Request $request
     * @param PaymentManager $paymentManager
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/webhook/payment', methods: ['POST'])]
    public function handlePayment(
        Request $request,
        PaymentManager $paymentManager,
        LoggerInterface $logger,
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            // 1. Detect gateway
            $gateway = $this->detectGateway($request);

            $logger->info('Webhook received', [
                'gateway' => $gateway,
                'headers' => array_keys($request->headers->all())
            ]);

            // 2. Parse payload
            $payload = json_decode($request->getContent(), true) ?? $request->request->all();

            // 3. Process webhook
            $result = $paymentManager->gateway($gateway)->handleWebhook(
                $payload,
                $request->headers->all()
            );

            if (!$result['success']) {
                $logger->error('Webhook processing failed', $result);
                return new JsonResponse(['error' => 'Processing failed'], 400);
            }

            $logger->info('Webhook processed', [
                'event_type' => $result['event_type'] ?? 'unknown',
                'transaction_id' => $result['transaction_id'] ?? null,
                'status' => $result['status'] ?? null
            ]);

            // 4. Find order in database (example with Doctrine)
            // $order = $em->getRepository(Order::class)
            //     ->findBy(['transaction_id' => $result['transaction_id']]);

            // if ($order) {
            //     $order->setStatus($result['status']);
            //     $em->flush();
            // }

            // 5. Dispatch events based on status
            $this->handleStatusChange($result['status'] ?? null, $result);

            return new JsonResponse(['status' => 'ok']);

        } catch (\Exception $e) {
            $logger->error('Webhook error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return 200 to stop webhook retries
            return new JsonResponse(['error' => 'Processing error'], 200);
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
        if ($request->headers->has('Stripe-Signature')) {
            return 'stripe';
        }

        // Check PayPal headers
        if ($request->headers->has('Paypal-Transmission-Id')) {
            return 'paypal';
        }

        // Fallback to query parameter
        return $request->query->get('gateway', 'paypal');
    }

    /**
     * Handle different payment statuses
     *
     * @param string|null $status
     * @param array $data
     */
    private function handleStatusChange(?string $status, array $data): void
    {
        match($status) {
            'completed' => $this->onPaymentCompleted($data),
            'failed' => $this->onPaymentFailed($data),
            'refunded' => $this->onPaymentRefunded($data),
            'cancelled' => $this->onPaymentCancelled($data),
            default => null
        };
    }

    /**
     * Payment completed
     */
    private function onPaymentCompleted(array $data): void
    {
        // Example: Send confirmation email
        // $this->mailer->send(...);

        // Example: Activate digital product
        // $product->activate();
    }

    /**
     * Payment failed
     */
    private function onPaymentFailed(array $data): void
    {
        // Example: Send failure notification
        // $this->mailer->send(...);
    }

    /**
     * Payment refunded
     */
    private function onPaymentRefunded(array $data): void
    {
        // Example: Revoke access
        // $order->revoke();
    }

    /**
     * Payment cancelled
     */
    private function onPaymentCancelled(array $data): void
    {
        // Example: Notify user
        // Notification::dispatch(...);
    }
}
