<?php

declare(strict_types=1);

namespace Database\Factories;

use App\BookingStatus;
use App\BookingType;
use App\Models\Booking;
use App\Models\Customer;
use App\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('+1 day', '+30 days');
        $endDate = (clone $startDate)->modify('+'.fake()->numberBetween(1, 7).' days');

        $totalPrice = fake()->randomFloat(2, 100, 1000);
        $discountAmount = 0;
        $finalPrice = $totalPrice - $discountAmount;

        return [
            'customer_id' => Customer::factory(),
            'booking_type' => fake()->randomElement(BookingType::cases()),
            'status' => BookingStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_price' => $totalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'number_of_people' => fake()->numberBetween(1, 10),
            'special_requests' => fake()->optional()->sentence(),
            'internal_notes' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'reminder_sent_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = fake()->dateTimeBetween('-30 days', '-7 days');
            $endDate = (clone $startDate)->modify('+'.fake()->numberBetween(1, 3).' days');

            return [
                'status' => BookingStatus::Completed,
                'payment_status' => PaymentStatus::Paid,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        });
    }

    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = fake()->dateTimeBetween('+1 day', '+7 days');
            $endDate = (clone $startDate)->modify('+'.fake()->numberBetween(1, 3).' days');

            return [
                'status' => BookingStatus::Confirmed,
                'payment_status' => PaymentStatus::Paid,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        });
    }

    public function conference(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_type' => BookingType::Conferentie,
        ]);
    }

    public function accommodation(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_type' => BookingType::Accommodation,
        ]);
    }

    public function coWorking(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_type' => BookingType::CoWorking,
        ]);
    }

    public function lightTherapy(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_type' => BookingType::LightTherapy,
        ]);
    }

    public function withDiscount(float $amount): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            $totalPrice = $attributes['total_price'];
            $finalPrice = $totalPrice - $amount;

            return [
                'discount_amount' => $amount,
                'final_price' => $finalPrice,
            ];
        });
    }

    public function forDateRange(\DateTime $start, \DateTime $end): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }
}
