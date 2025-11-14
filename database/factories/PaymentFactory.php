<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'subscription_id' => null,
            'customer_id' => Customer::factory(),
            'amount' => fake()->randomFloat(2, 50, 1000),
            'status' => PaymentStatus::Pending,
            'payment_method' => 'mollie',
            'transaction_id' => 'tr_'.fake()->uuid(),
            'refund_amount' => null,
            'paid_at' => null,
            'refunded_at' => null,
            'metadata' => [
                'mollie_payment_id' => 'tr_'.fake()->uuid(),
                'mollie_checkout_url' => fake()->url(),
            ],
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $attributes['amount'];

            return [
                'status' => PaymentStatus::Refunded,
                'refund_amount' => $amount,
                'refunded_at' => now(),
            ];
        });
    }

    public function partiallyRefunded(float $refundAmount): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PartiallyRefunded,
            'refund_amount' => $refundAmount,
            'refunded_at' => now(),
        ]);
    }
}
