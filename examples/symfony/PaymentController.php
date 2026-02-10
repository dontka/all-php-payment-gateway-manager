<?php

declare(strict_types=1);

namespace App\Controller;

use PaymentGateway\Core\PaymentManager;
use PaymentGateway\Events\PaymentSuccessEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

/**
 * Symfony Payment Controller Example
 *
 * Demonstrates payment handling in Symfony
 *
 * Routes:
 *   POST /payment/charge
 *   GET  /payment/success
 *   POST /webhook/payment
 */
#[Route('/payment')]
class PaymentController extends AbstractController
{
    /**
     * Charge endpoint
     *
     * @param Request $request
     * @param PaymentManager $paymentManager
     * @param LoggerInterface $logger
     * @return Response
     */
    #[Route('/charge', methods: ['POST'])]
    public function charge(
        Request $request,
        PaymentManager $paymentManager,
        LoggerInterface $logger
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);

            // Validate input
            $this->validateInput($data);

            $gateway = $data['gateway'] ?? 'paypal';

            $logger->info('Processing payment', [
                'gateway' => $gateway,
                'amount' => $data['amount'],
                'currency' => $data['currency']
            ]);

            // Process payment
            $result = $paymentManager->gateway($gateway)->charge([
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'customer' => [
                    'email' => $data['customer_email'],
                    'name' => $data['customer_name'] ?? null
                ],
                'description' => $data['description'] ?? 'Payment'
            ]);

            if ($result['success']) {
                $logger->info('Payment initiated', [
                    'transaction_id' => $result['transaction_id'] ?? $result['order_id'] ?? null
                ]);

                return new JsonResponse([
                    'success' => true,
                    'transaction_id' => $result['transaction_id'] ?? $result['order_id'] ?? null,
                    'approval_link' => $result['approval_link'] ?? null
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'error' => $result['error'] ?? 'Payment failed'
            ], 400);

        } catch (\Exception $e) {
            $logger->error('Payment error: ' . $e->getMessage());

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Success page (after PayPal approval)
     *
     * @param Request $request
     * @param PaymentManager $paymentManager
     * @param LoggerInterface $logger
     * @return Response
     */
    #[Route('/success', methods: ['GET'])]
    public function success(
        Request $request,
        PaymentManager $paymentManager,
        LoggerInterface $logger
    ): Response {
        $orderId = $request->query->get('orderId');

        if ($orderId) {
            try {
                // Capture the PayPal order
                $result = $paymentManager->gateway('paypal')
                    ->captureOrderPayment($orderId);

                if ($result['success']) {
                    $logger->info('Payment captured', [
                        'capture_id' => $result['capture_id']
                    ]);

                    return new Response('✅ Payment successful!');
                }

            } catch (\Exception $e) {
                $logger->error('Capture failed: ' . $e->getMessage());
            }
        }

        return new Response('❌ Payment failed', 400);
    }

    /**
     * Refund a payment
     *
     * @param Request $request
     * @param PaymentManager $paymentManager
     * @param LoggerInterface $logger
     * @return JsonResponse
     */
    #[Route('/refund', methods: ['POST'])]
    public function refund(
        Request $request,
        PaymentManager $paymentManager,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            $transactionId = $data['transaction_id'] ?? null;
            $amount = $data['amount'] ?? null;
            $gateway = $data['gateway'] ?? 'paypal';

            if (!$transactionId) {
                return new JsonResponse(['error' => 'Missing transaction_id'], 400);
            }

            $result = $paymentManager->gateway($gateway)->refund(
                $transactionId,
                $amount,
                ['reason' => $data['reason'] ?? 'Customer request']
            );

            if ($result['success']) {
                $logger->info('Refund processed', [
                    'refund_id' => $result['refund_id']
                ]);

                return new JsonResponse(['success' => true, 'refund_id' => $result['refund_id']]);
            }

            return new JsonResponse(['error' => $result['error'] ?? 'Refund failed'], 400);

        } catch (\Exception $e) {
            $logger->error('Refund error: ' . $e->getMessage());
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Verify payment status
     *
     * @param Request $request
     * @param PaymentManager $paymentManager
     * @return JsonResponse
     */
    #[Route('/verify/{transactionId}', methods: ['GET'])]
    public function verify(
        string $transactionId,
        Request $request,
        PaymentManager $paymentManager
    ): JsonResponse {
        try {
            $gateway = $request->query->get('gateway', 'paypal');

            $status = $paymentManager->gateway($gateway)->verify($transactionId);

            return new JsonResponse($status);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Validate payment input
     */
    private function validateInput(array $data): void
    {
        if (empty($data['amount']) || $data['amount'] <= 0) {
            throw new \InvalidArgumentException('Invalid amount');
        }

        if (empty($data['currency'])) {
            throw new \InvalidArgumentException('Missing currency');
        }

        if (empty($data['customer_email'])) {
            throw new \InvalidArgumentException('Missing customer_email');
        }
    }
}
