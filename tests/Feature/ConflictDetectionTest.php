<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Space;
use App\Services\BookingValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\SpacesAndPricingSeeder::class);
    $this->service = app(BookingValidationService::class);
});

describe('Universe conflicts with component spaces', function () {
    it('booking Universe blocks Glow and Ray', function () {
        $universe = Space::where('slug', 'the-universe')->first();
        $glow = Space::where('slug', 'the-glow')->first();
        $ray = Space::where('slug', 'the-ray')->first();

        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Book The Universe
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($universe->id);

        // Check that Glow and Ray are blocked
        $glowAvailable = $this->service->isSpaceAvailable($glow, $start, $end);
        $rayAvailable = $this->service->isSpaceAvailable($ray, $start, $end);

        expect($glowAvailable)->toBeFalse()
            ->and($rayAvailable)->toBeFalse();
    });

    it('booking Glow blocks Universe', function () {
        $universe = Space::where('slug', 'the-universe')->first();
        $glow = Space::where('slug', 'the-glow')->first();

        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Book The Glow
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($glow->id);

        // Check that Universe is blocked
        $universeAvailable = $this->service->isSpaceAvailable($universe, $start, $end);

        expect($universeAvailable)->toBeFalse();
    });

    it('booking Ray blocks Universe', function () {
        $universe = Space::where('slug', 'the-universe')->first();
        $ray = Space::where('slug', 'the-ray')->first();

        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Book The Ray
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($ray->id);

        // Check that Universe is blocked
        $universeAvailable = $this->service->isSpaceAvailable($universe, $start, $end);

        expect($universeAvailable)->toBeFalse();
    });

    it('booking both Glow and Ray separately blocks Universe', function () {
        $universe = Space::where('slug', 'the-universe')->first();
        $glow = Space::where('slug', 'the-glow')->first();
        $ray = Space::where('slug', 'the-ray')->first();

        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Book The Glow
        $booking1 = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking1->spaces()->attach($glow->id);

        // Book The Ray
        $booking2 = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking2->spaces()->attach($ray->id);

        // Check that Universe is blocked
        $universeAvailable = $this->service->isSpaceAvailable($universe, $start, $end);

        expect($universeAvailable)->toBeFalse();
    });
});

describe('Multiple overlapping bookings', function () {
    it('detects multiple conflicts', function () {
        $glow = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(15);

        // Create first booking
        $booking1 = Booking::factory()->confirmed()->create([
            'start_date' => now()->addDays(8),
            'end_date' => now()->addDays(12),
        ]);
        $booking1->spaces()->attach($glow->id);

        // Create second booking
        $booking2 = Booking::factory()->confirmed()->create([
            'start_date' => now()->addDays(13),
            'end_date' => now()->addDays(17),
        ]);
        $booking2->spaces()->attach($glow->id);

        $conflicts = $this->service->getConflictingBookings(
            collect([$glow]),
            $start,
            $end
        );

        expect($conflicts)->toHaveCount(2);
    });

    it('detects conflicts across multiple spaces', function () {
        $glow = Space::where('slug', 'the-glow')->first();
        $ray = Space::where('slug', 'the-ray')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Book Glow
        $booking1 = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking1->spaces()->attach($glow->id);

        // Book Ray
        $booking2 = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking2->spaces()->attach($ray->id);

        $conflicts = $this->service->getConflictingBookings(
            collect([$glow, $ray]),
            $start,
            $end
        );

        expect($conflicts)->toHaveCount(2);
    });
});

describe('Non-overlapping bookings', function () {
    it('allows back-to-back bookings', function () {
        $glow = Space::where('slug', 'the-glow')->first();

        // First booking ends at noon
        $booking1 = Booking::factory()->confirmed()->create([
            'start_date' => now()->addDays(10)->setTime(9, 0),
            'end_date' => now()->addDays(10)->setTime(12, 0),
        ]);
        $booking1->spaces()->attach($glow->id);

        // Second booking starts at noon
        $start = now()->addDays(10)->setTime(12, 0);
        $end = now()->addDays(10)->setTime(17, 0);

        $available = $this->service->isSpaceAvailable($glow, $start, $end);

        expect($available)->toBeTrue();
    });

    it('allows bookings on different days', function () {
        $glow = Space::where('slug', 'the-glow')->first();

        // Booking on day 10-12
        $booking1 = Booking::factory()->confirmed()->create([
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(12),
        ]);
        $booking1->spaces()->attach($glow->id);

        // Check availability on day 15-17
        $start = now()->addDays(15);
        $end = now()->addDays(17);

        $available = $this->service->isSpaceAvailable($glow, $start, $end);

        expect($available)->toBeTrue();
    });
});

describe('Co-working overflow capacity', function () {
    it('calculates correct capacity when all rooms available', function () {
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $capacity = $this->service->getCoWorkingCapacity($start, $end);

        // Co-working (6) + Glow (6) + Ray (10) = 22
        expect($capacity)->toBe(22);
    });

    it('reduces capacity when Glow is booked', function () {
        $glow = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($glow->id);

        $capacity = $this->service->getCoWorkingCapacity($start, $end);

        // Co-working (6) + Ray (10) = 16
        expect($capacity)->toBe(16);
    });

    it('reduces capacity when Ray is booked', function () {
        $ray = Space::where('slug', 'the-ray')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($ray->id);

        $capacity = $this->service->getCoWorkingCapacity($start, $end);

        // Co-working (6) + Glow (6) = 12
        expect($capacity)->toBe(12);
    });

    it('reduces capacity when Universe is booked', function () {
        $universe = Space::where('slug', 'the-universe')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($universe->id);

        $capacity = $this->service->getCoWorkingCapacity($start, $end);

        // Only base co-working space = 6
        expect($capacity)->toBe(6);
    });

    it('only includes base capacity when all conference rooms booked', function () {
        $glow = Space::where('slug', 'the-glow')->first();
        $ray = Space::where('slug', 'the-ray')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Book Glow
        $booking1 = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking1->spaces()->attach($glow->id);

        // Book Ray
        $booking2 = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking2->spaces()->attach($ray->id);

        $capacity = $this->service->getCoWorkingCapacity($start, $end);

        // Only base co-working = 6
        expect($capacity)->toBe(6);
    });
});

describe('Combined space booking validation', function () {
    it('allows booking Universe when both Glow and Ray available', function () {
        $universe = Space::where('slug', 'the-universe')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $canBook = $this->service->canBookCombinedSpace($universe, $start, $end);

        expect($canBook)->toBeTrue();
    });

    it('prevents booking Universe when Glow is booked', function () {
        $universe = Space::where('slug', 'the-universe')->first();
        $glow = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($glow->id);

        $canBook = $this->service->canBookCombinedSpace($universe, $start, $end);

        expect($canBook)->toBeFalse();
    });

    it('prevents booking Universe when Ray is booked', function () {
        $universe = Space::where('slug', 'the-universe')->first();
        $ray = Space::where('slug', 'the-ray')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($ray->id);

        $canBook = $this->service->canBookCombinedSpace($universe, $start, $end);

        expect($canBook)->toBeFalse();
    });
});

describe('Edge cases', function () {
    it('handles bookings starting exactly when another ends', function () {
        $glow = Space::where('slug', 'the-glow')->first();

        // First booking
        $booking1 = Booking::factory()->confirmed()->create([
            'start_date' => now()->addDays(10)->setTime(9, 0),
            'end_date' => now()->addDays(10)->setTime(12, 0),
        ]);
        $booking1->spaces()->attach($glow->id);

        // Second booking starts when first ends
        $start = now()->addDays(10)->setTime(12, 0);
        $end = now()->addDays(10)->setTime(17, 0);

        $available = $this->service->isSpaceAvailable($glow, $start, $end);

        expect($available)->toBeTrue();
    });

    it('detects one-minute overlap', function () {
        $glow = Space::where('slug', 'the-glow')->first();

        $booking1 = Booking::factory()->confirmed()->create([
            'start_date' => now()->addDays(10)->setTime(9, 0),
            'end_date' => now()->addDays(10)->setTime(12, 1),
        ]);
        $booking1->spaces()->attach($glow->id);

        // Overlaps by 1 minute
        $start = now()->addDays(10)->setTime(12, 0);
        $end = now()->addDays(10)->setTime(17, 0);

        $available = $this->service->isSpaceAvailable($glow, $start, $end);

        expect($available)->toBeFalse();
    });

    it('handles booking that completely contains another', function () {
        $glow = Space::where('slug', 'the-glow')->first();

        // Outer booking
        $booking1 = Booking::factory()->confirmed()->create([
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(20),
        ]);
        $booking1->spaces()->attach($glow->id);

        // Try to book within the existing booking
        $start = now()->addDays(12);
        $end = now()->addDays(15);

        $available = $this->service->isSpaceAvailable($glow, $start, $end);

        expect($available)->toBeFalse();
    });
});
