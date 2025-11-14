<?php

declare(strict_types=1);

namespace Database\Factories;

use App\BookingType;
use App\DurationType;
use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = now();
        $endsAt = now()->addMonth();

        return [
            'customer_id' => Customer::factory(),
            'booking_type' => fake()->randomElement([
                BookingType::CoWorking,
                BookingType::LightTherapy,
            ]),
            'duration_type' => DurationType::Monthly,
            'quantity' => 1,
            'price' => fake()->randomFloat(2, 100, 500),
            'usage_limit' => fake()->numberBetween(4, 12),
            'usage_count' => 0,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'cancelled_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->addWeek(),
            'cancelled_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'cancelled_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subMonth(),
        ]);
    }

    public function withUsage(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_count' => $count,
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => null,
        ]);
    }

    public function coWorking(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_type' => BookingType::CoWorking,
            'price' => 300.00,
            'usage_limit' => null,
        ]);
    }

    public function lightTherapy(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_type' => BookingType::LightTherapy,
            'price' => 400.00,
            'usage_limit' => 4,
        ]);
    }
}
