<?php

namespace App\Services;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mollie\Laravel\Facades\Mollie;

class PaymentService
{
    /**
     * Create a Mollie payment for a booking
     */
    public function createPayment(Booking $booking): Payment
    {
        return DB::transaction(function () use ($booking) {
            $customer = $booking->customer;

            // Ensure customer has Mollie customer ID
            if (! $customer->mollie_customer_id) {
                $customer->createAsMollieCustomer();
            }

            // Convert amount to cents (Mollie expects minor units)
            $amountInCents = (int) ($booking->final_price * 100);

            // Create Mollie payment
            $molliePayment = $customer->charge(
                $amountInCents,
                [
                    'description' => "Booking #{$booking->id} - {$booking->booking_type->value}",
                    'redirectUrl' => route('booking.confirmation', $booking->id),
                    'webhookUrl' => route('mollie.webhook.payment'),
                    'metadata' => [
                        'booking_id' => $booking->id,
                        'customer_id' => $customer->id,
                        'booking_type' => $booking->booking_type->value,
                    ],
                ]
            );

            // Create payment record in database
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'customer_id' => $customer->id,
                'amount' => $booking->final_price,
                'status' => PaymentStatus::Pending,
                'payment_method' => 'mollie',
                'transaction_id' => $molliePayment->id,
                'metadata' => [
                    'mollie_payment_id' => $molliePayment->id,
                    'mollie_checkout_url' => $molliePayment->getCheckoutUrl(),
                ],
            ]);

            Log::info('Payment created for booking', [
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'mollie_payment_id' => $molliePayment->id,
                'amount' => $booking->final_price,
            ]);

            return $payment;
        });
    }

    /**
     * Handle successful payment webhook from Mollie
     */
    public function handlePaymentPaid(string $molliePaymentId): void
    {
        DB::transaction(function () use ($molliePaymentId) {
            $payment = Payment::where('transaction_id', $molliePaymentId)->firstOrFail();

            // Prevent duplicate processing
            if ($payment->status === PaymentStatus::Paid) {
                Log::info('Payment already marked as paid, skipping', [
                    'payment_id' => $payment->id,
                    'mollie_payment_id' => $molliePaymentId,
                ]);

                return;
            }

            // Mark payment as paid
            $payment->markAsPaid();

            // Update booking status
            $booking = $payment->booking;
            if ($booking) {
                $booking->status = BookingStatus::Confirmed;
                $booking->payment_status = PaymentStatus::Paid;
                $booking->save();

                Log::info('Booking confirmed after payment', [
                    'booking_id' => $booking->id,
                    'payment_id' => $payment->id,
                ]);

                // TODO: Fire event for booking confirmed notification
                // event(new BookingConfirmed($booking));
            }

            Log::info('Payment marked as paid', [
                'payment_id' => $payment->id,
                'mollie_payment_id' => $molliePaymentId,
            ]);
        });
    }

    /**
     * Handle failed payment webhook from Mollie
     */
    public function handlePaymentFailed(string $molliePaymentId): void
    {
        DB::transaction(function () use ($molliePaymentId) {
            $payment = Payment::where('transaction_id', $molliePaymentId)->firstOrFail();

            // Prevent duplicate processing
            if ($payment->status === PaymentStatus::Failed) {
                Log::info('Payment already marked as failed, skipping', [
                    'payment_id' => $payment->id,
                    'mollie_payment_id' => $molliePaymentId,
                ]);

                return;
            }

            // Mark payment as failed
            $payment->status = PaymentStatus::Failed;
            $payment->save();

            // Don't cancel booking - allow customer to retry payment
            $booking = $payment->booking;
            if ($booking) {
                Log::warning('Payment failed for booking', [
                    'booking_id' => $booking->id,
                    'payment_id' => $payment->id,
                ]);

                // TODO: Fire event for payment failed notification
                // event(new PaymentFailed($payment));
            }

            Log::info('Payment marked as failed', [
                'payment_id' => $payment->id,
                'mollie_payment_id' => $molliePaymentId,
            ]);
        });
    }

    /**
     * Process a refund for a payment (full or partial)
     */
    public function refundPayment(Payment $payment, ?float $amount = null): Payment
    {
        return DB::transaction(function () use ($payment, $amount) {
            // Validate payment can be refunded
            if ($payment->status !== PaymentStatus::Paid && $payment->status !== PaymentStatus::PartiallyRefunded) {
                throw new \InvalidArgumentException('Only paid or partially refunded payments can be refunded');
            }

            // Determine refund amount
            $refundAmount = $amount ?? (float) $payment->amount;
            $remainingAmount = (float) $payment->amount - (float) ($payment->refund_amount ?? 0);

            if ($refundAmount > $remainingAmount) {
                throw new \InvalidArgumentException('Refund amount exceeds remaining refundable amount');
            }

            // Convert to cents for Mollie
            $refundAmountInCents = (int) ($refundAmount * 100);

            // Process refund via Mollie
            $molliePayment = Mollie::api()->payments->get($payment->transaction_id);
            $mollieRefund = $molliePayment->refund([
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format($refundAmount, 2, '.', ''),
                ],
            ]);

            // Update payment record
            $payment->refund($refundAmount);

            // If booking exists, consider canceling it
            $booking = $payment->booking;
            if ($booking && $payment->status === PaymentStatus::Refunded) {
                $booking->cancel('Payment refunded');

                Log::info('Booking cancelled after full refund', [
                    'booking_id' => $booking->id,
                    'payment_id' => $payment->id,
                ]);
            }

            Log::info('Payment refunded', [
                'payment_id' => $payment->id,
                'refund_amount' => $refundAmount,
                'mollie_refund_id' => $mollieRefund->id,
                'new_status' => $payment->status->value,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Handle expired payment webhook from Mollie
     */
    public function handlePaymentExpired(string $molliePaymentId): void
    {
        DB::transaction(function () use ($molliePaymentId) {
            $payment = Payment::where('transaction_id', $molliePaymentId)->firstOrFail();

            // Mark payment as failed (expired)
            $payment->status = PaymentStatus::Failed;
            $payment->metadata = array_merge($payment->metadata ?? [], [
                'expired_at' => now()->toIso8601String(),
            ]);
            $payment->save();

            Log::info('Payment expired', [
                'payment_id' => $payment->id,
                'mollie_payment_id' => $molliePaymentId,
            ]);
        });
    }

    /**
     * Handle cancelled payment webhook from Mollie
     */
    public function handlePaymentCancelled(string $molliePaymentId): void
    {
        DB::transaction(function () use ($molliePaymentId) {
            $payment = Payment::where('transaction_id', $molliePaymentId)->firstOrFail();

            // Mark payment as failed (cancelled by user)
            $payment->status = PaymentStatus::Failed;
            $payment->metadata = array_merge($payment->metadata ?? [], [
                'cancelled_at' => now()->toIso8601String(),
            ]);
            $payment->save();

            Log::info('Payment cancelled by user', [
                'payment_id' => $payment->id,
                'mollie_payment_id' => $molliePaymentId,
            ]);
        });
    }
}
