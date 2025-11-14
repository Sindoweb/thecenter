<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Mollie\Laravel\Facades\Mollie;

class MollieWebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Handle payment status update webhooks from Mollie
     */
    public function handlePaymentWebhook(Request $request): Response
    {
        try {
            // Get payment ID from request
            $paymentId = $request->input('id');

            if (! $paymentId) {
                Log::warning('Mollie payment webhook received without payment ID');

                return response('Missing payment ID', 400);
            }

            // Fetch payment status from Mollie API
            $molliePayment = Mollie::api()->payments->get($paymentId);

            Log::info('Mollie payment webhook received', [
                'payment_id' => $paymentId,
                'status' => $molliePayment->status,
            ]);

            // Handle payment based on status
            match ($molliePayment->status) {
                'paid' => $this->paymentService->handlePaymentPaid($paymentId),
                'failed' => $this->paymentService->handlePaymentFailed($paymentId),
                'expired' => $this->paymentService->handlePaymentExpired($paymentId),
                'canceled' => $this->paymentService->handlePaymentCancelled($paymentId),
                'pending', 'open' => null, // These are transitional states, no action needed
                default => Log::warning('Unknown Mollie payment status', [
                    'payment_id' => $paymentId,
                    'status' => $molliePayment->status,
                ]),
            };

            return response('OK', 200);
        } catch (\Throwable $e) {
            Log::error('Error processing Mollie payment webhook', [
                'error' => $e->getMessage(),
                'payment_id' => $request->input('id'),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 to prevent Mollie from retrying
            // We've logged the error for investigation
            return response('OK', 200);
        }
    }

    /**
     * Handle subscription status update webhooks from Mollie
     */
    public function handleSubscriptionWebhook(Request $request): Response
    {
        try {
            // Get subscription ID from request
            $subscriptionId = $request->input('id');

            if (! $subscriptionId) {
                Log::warning('Mollie subscription webhook received without subscription ID');

                return response('Missing subscription ID', 400);
            }

            // Fetch subscription status from Mollie API
            $mollieSubscription = Mollie::api()->subscriptions->get($subscriptionId);

            Log::info('Mollie subscription webhook received', [
                'subscription_id' => $subscriptionId,
                'status' => $mollieSubscription->status,
            ]);

            // Handle subscription based on status
            match ($mollieSubscription->status) {
                'active' => $this->handleSubscriptionActive($mollieSubscription),
                'suspended' => $this->handleSubscriptionSuspended($mollieSubscription),
                'canceled' => $this->handleSubscriptionCanceled($mollieSubscription),
                'completed' => $this->handleSubscriptionCompleted($mollieSubscription),
                'pending' => null, // Transitional state
                default => Log::warning('Unknown Mollie subscription status', [
                    'subscription_id' => $subscriptionId,
                    'status' => $mollieSubscription->status,
                ]),
            };

            return response('OK', 200);
        } catch (\Throwable $e) {
            Log::error('Error processing Mollie subscription webhook', [
                'error' => $e->getMessage(),
                'subscription_id' => $request->input('id'),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 to prevent Mollie from retrying
            return response('OK', 200);
        }
    }

    /**
     * Handle active subscription status
     */
    protected function handleSubscriptionActive($mollieSubscription): void
    {
        Log::info('Subscription activated', [
            'mollie_subscription_id' => $mollieSubscription->id,
        ]);

        // Subscription is now active - handled by Cashier
        // Our subscription record should already exist from creation
    }

    /**
     * Handle suspended subscription status
     */
    protected function handleSubscriptionSuspended($mollieSubscription): void
    {
        Log::warning('Subscription suspended', [
            'mollie_subscription_id' => $mollieSubscription->id,
        ]);

        // TODO: Find our subscription record and mark as suspended
        // This might happen due to payment failures
    }

    /**
     * Handle canceled subscription status
     */
    protected function handleSubscriptionCanceled($mollieSubscription): void
    {
        Log::info('Subscription canceled', [
            'mollie_subscription_id' => $mollieSubscription->id,
        ]);

        // Cashier will handle the cancellation
        // Our subscription record should already be marked as cancelled
    }

    /**
     * Handle completed subscription status
     */
    protected function handleSubscriptionCompleted($mollieSubscription): void
    {
        Log::info('Subscription completed', [
            'mollie_subscription_id' => $mollieSubscription->id,
        ]);

        // Subscription has reached its end date
        // Our subscription record should reflect this via ends_at date
    }
}
