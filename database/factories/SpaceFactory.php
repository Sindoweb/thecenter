<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Space;
use App\SpaceType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Space>
 */
class SpaceFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(SpaceType::cases()),
            'capacity' => fake()->numberBetween(2, 16),
            'features' => [
                'WiFi',
                'Natural Light',
            ],
            'can_combine_with' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function conferenceRoom(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SpaceType::ConferenceRoom,
            'capacity' => fake()->numberBetween(6, 10),
            'features' => [
                'WiFi',
                'Projector',
                'Whiteboard',
                'Video Conferencing',
                'Natural Light',
                'Coffee/Tea Station',
            ],
        ]);
    }

    public function accommodation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SpaceType::Accommodation,
            'capacity' => 2,
            'features' => [
                'Queen Bed',
                'Private Bathroom',
                'WiFi',
                'Kitchenette',
                'Breakfast Included',
                'Natural Light',
            ],
        ]);
    }

    public function coWorking(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SpaceType::CoWorking,
            'capacity' => fake()->numberBetween(4, 8),
            'features' => [
                'High-Speed WiFi',
                'Hot Desks',
                'Ergonomic Chairs',
                'Coffee/Tea',
                'Printing',
                'Natural Light',
            ],
        ]);
    }

    public function therapyRoom(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SpaceType::TherapyRoom,
            'capacity' => 2,
            'features' => [
                'Light Therapy Equipment',
                'Comfortable Seating',
                'Ambient Sound System',
                'Climate Control',
                'Private Space',
            ],
        ]);
    }

    public function combined(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SpaceType::Combined,
            'capacity' => 16,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
