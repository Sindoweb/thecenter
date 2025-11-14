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

describe('isSpaceAvailable', function () {
    it('returns true when no conflicts exist', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $result = $this->service->isSpaceAvailable($space, $start, $end);

        expect($result)->toBeTrue();
    });

    it('returns false when space is already booked', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Create conflicting booking
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($space->id);

        $result = $this->service->isSpaceAvailable($space, $start, $end);

        expect($result)->toBeFalse();
    });

    it('excludes specified booking when checking availability', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Create a booking we want to exclude
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($space->id);

        // Should return true when excluding this booking
        $result = $this->service->isSpaceAvailable($space, $start, $end, $booking->id);

        expect($result)->toBeTrue();
    });

    it('ignores cancelled bookings', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $booking = Booking::factory()->cancelled()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($space->id);

        $result = $this->service->isSpaceAvailable($space, $start, $end);

        expect($result)->toBeTrue();
    });
});

describe('areSpacesAvailable', function () {
    it('returns true when all spaces are available', function () {
        $glow = Space::where('slug', 'the-glow')->first();
        $ray = Space::where('slug', 'the-ray')->first();
        $spaces = collect([$glow, $ray]);

        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $result = $this->service->areSpacesAvailable($spaces, $start, $end);

        expect($result)->toBeTrue();
    });

    it('returns false when any space is unavailable', function () {
        $glow = Space::where('slug', 'the-glow')->first();
        $ray = Space::where('slug', 'the-ray')->first();
        $spaces = collect([$glow, $ray]);

        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Book one of the spaces
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($glow->id);

        $result = $this->service->areSpacesAvailable($spaces, $start, $end);

        expect($result)->toBeFalse();
    });
});

describe('getConflictingBookings', function () {
    it('finds overlapping bookings', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(15);

        // Create overlapping booking
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => now()->addDays(12),
            'end_date' => now()->addDays(17),
        ]);
        $booking->spaces()->attach($space->id);

        $conflicts = $this->service->getConflictingBookings(
            collect([$space]),
            $start,
            $end
        );

        expect($conflicts)->toHaveCount(1)
            ->and($conflicts->first()->id)->toBe($booking->id);
    });

    it('does not find non-overlapping bookings', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(15);

        // Create non-overlapping booking (before)
        $booking1 = Booking::factory()->confirmed()->create([
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(5),
        ]);
        $booking1->spaces()->attach($space->id);

        // Create non-overlapping booking (after)
        $booking2 = Booking::factory()->confirmed()->create([
            'start_date' => now()->addDays(20),
            'end_date' => now()->addDays(25),
        ]);
        $booking2->spaces()->attach($space->id);

        $conflicts = $this->service->getConflictingBookings(
            collect([$space]),
            $start,
            $end
        );

        expect($conflicts)->toHaveCount(0);
    });
});

describe('combined space conflicts', function () {
    it('Universe booking blocks Glow', function () {
        $universe = Space::where('slug', 'the-universe')->first();
        $glow = Space::where('slug', 'the-glow')->first();

        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Book The Universe
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($universe->id);

        // Try to check if Glow is available
        $result = $this->service->isSpaceAvailable($glow, $start, $end);

        expect($result)->toBeFalse();
    });

    it('Universe booking blocks Ray', function () {
        $universe = Space::where('slug', 'the-universe')->first();
        $ray = Space::where('slug', 'the-ray')->first();

        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Book The Universe
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($universe->id);

        // Try to check if Ray is available
        $result = $this->service->isSpaceAvailable($ray, $start, $end);

        expect($result)->toBeFalse();
    });

    it('Glow booking blocks Universe', function () {
        $glow = Space::where('slug', 'the-glow')->first();
        $universe = Space::where('slug', 'the-universe')->first();

        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Book The Glow
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($glow->id);

        // Try to check if Universe is available
        $result = $this->service->isSpaceAvailable($universe, $start, $end);

        expect($result)->toBeFalse();
    });

    it('Ray booking blocks Universe', function () {
        $ray = Space::where('slug', 'the-ray')->first();
        $universe = Space::where('slug', 'the-universe')->first();

        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Book The Ray
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($ray->id);

        // Try to check if Universe is available
        $result = $this->service->isSpaceAvailable($universe, $start, $end);

        expect($result)->toBeFalse();
    });

    it('allows booking Glow and Ray separately when Universe is not booked', function () {
        $glow = Space::where('slug', 'the-glow')->first();
        $ray = Space::where('slug', 'the-ray')->first();

        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $glowAvailable = $this->service->isSpaceAvailable($glow, $start, $end);
        $rayAvailable = $this->service->isSpaceAvailable($ray, $start, $end);

        expect($glowAvailable)->toBeTrue()
            ->and($rayAvailable)->toBeTrue();
    });
});

describe('validateBooking', function () {
    it('returns error when end date is before start date', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(5);

        $errors = $this->service->validateBooking(
            collect([$space]),
            $start,
            $end,
            6
        );

        expect($errors)->toHaveCount(1)
            ->and($errors[0])->toContain('End date must be after start date');
    });

    it('returns error when start date is in the past', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->subDays(1);
        $end = now()->addDays(2);

        $errors = $this->service->validateBooking(
            collect([$space]),
            $start,
            $end,
            6
        );

        expect($errors)->toContain('Start date must be in the future');
    });

    it('allows past dates when updating existing booking', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->subDays(1);
        $end = now()->addDays(2);

        $booking = Booking::factory()->create();

        $errors = $this->service->validateBooking(
            collect([$space]),
            $start,
            $end,
            6,
            $booking->id
        );

        expect($errors)->not->toContain('Start date must be in the future');
    });

    it('returns error when capacity is exceeded', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $errors = $this->service->validateBooking(
            collect([$space]),
            $start,
            $end,
            100 // Exceeds capacity
        );

        expect($errors)->toContain('Number of people (100) exceeds total capacity (6)');
    });

    it('returns error when spaces are unavailable', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Create conflicting booking
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($space->id);

        $errors = $this->service->validateBooking(
            collect([$space]),
            $start,
            $end,
            6
        );

        expect($errors)->not->toBeEmpty()
            ->and($errors[0])->toContain('not available');
    });

    it('returns empty array for valid booking', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $errors = $this->service->validateBooking(
            collect([$space]),
            $start,
            $end,
            6
        );

        expect($errors)->toBeEmpty();
    });
});

describe('getAvailableCapacity', function () {
    it('returns capacity when space is available', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $capacity = $this->service->getAvailableCapacity($space, $start, $end);

        expect($capacity)->toBe(6);
    });

    it('returns zero when space is booked', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($space->id);

        $capacity = $this->service->getAvailableCapacity($space, $start, $end);

        expect($capacity)->toBe(0);
    });
});

describe('getCoWorkingCapacity', function () {
    it('includes base co-working capacity', function () {
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $capacity = $this->service->getCoWorkingCapacity($start, $end);

        expect($capacity)->toBeGreaterThanOrEqual(6);
    });

    it('includes overflow rooms when available', function () {
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $capacity = $this->service->getCoWorkingCapacity($start, $end);

        // Should include co-working (6) + The Glow (6) + The Ray (10) = 22
        expect($capacity)->toBe(22);
    });

    it('excludes booked conference rooms from overflow', function () {
        $glow = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Book The Glow
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($glow->id);

        $capacity = $this->service->getCoWorkingCapacity($start, $end);

        // Should include co-working (6) + The Ray (10) = 16
        expect($capacity)->toBe(16);
    });
});

describe('canBookCombinedSpace', function () {
    it('returns true when all required spaces are available', function () {
        $universe = Space::where('slug', 'the-universe')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $result = $this->service->canBookCombinedSpace($universe, $start, $end);

        expect($result)->toBeTrue();
    });

    it('returns false when one required space is booked', function () {
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

        $result = $this->service->canBookCombinedSpace($universe, $start, $end);

        expect($result)->toBeFalse();
    });

    it('returns false for space without combinable spaces', function () {
        $space = Space::where('slug', 'co-working')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $result = $this->service->canBookCombinedSpace($space, $start, $end);

        expect($result)->toBeFalse();
    });
});

describe('getAvailableSpaces', function () {
    it('returns all spaces when none are booked', function () {
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $available = $this->service->getAvailableSpaces($start, $end);

        expect($available->count())->toBeGreaterThan(0);
    });

    it('excludes booked spaces', function () {
        $glow = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $booking->spaces()->attach($glow->id);

        $available = $this->service->getAvailableSpaces($start, $end);

        expect($available->pluck('id')->toArray())->not->toContain($glow->id);
    });
});
