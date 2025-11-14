<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Space;
use App\SpaceType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\SpacesAndPricingSeeder::class);
});

describe('Space model', function () {
    it('can create a space', function () {
        $space = Space::factory()->create([
            'name' => 'Test Room',
            'slug' => 'test-room',
            'capacity' => 10,
        ]);

        expect($space->name)->toBe('Test Room')
            ->and($space->slug)->toBe('test-room')
            ->and($space->capacity)->toBe(10)
            ->and($space->is_active)->toBeTrue();
    });

    it('has correct relationships', function () {
        $space = Space::where('slug', 'the-glow')->first();

        expect($space->pricingRules)->not->toHaveCount(0)
            ->and($space->bookings)->toHaveCount(0);
    });

    it('casts features to array', function () {
        $space = Space::factory()->create([
            'features' => ['WiFi', 'Projector', 'Whiteboard'],
        ]);

        expect($space->features)->toBeArray()
            ->and($space->features)->toContain('WiFi', 'Projector');
    });

    it('casts can_combine_with to array', function () {
        $glow = Space::where('slug', 'the-glow')->first();
        $ray = Space::where('slug', 'the-ray')->first();

        expect($glow->can_combine_with)->toBeArray()
            ->and($glow->can_combine_with)->toContain($ray->id);
    });
});

describe('Space combination logic', function () {
    it('canCombineWith returns true for combinable spaces', function () {
        $glow = Space::where('slug', 'the-glow')->first();
        $ray = Space::where('slug', 'the-ray')->first();

        expect($glow->canCombineWith($ray))->toBeTrue();
    });

    it('canCombineWith returns false for non-combinable spaces', function () {
        $glow = Space::where('slug', 'the-glow')->first();
        $coWorking = Space::where('slug', 'co-working')->first();

        expect($glow->canCombineWith($coWorking))->toBeFalse();
    });

    it('canCombineWith returns false when can_combine_with is null', function () {
        $space1 = Space::factory()->create(['can_combine_with' => null]);
        $space2 = Space::factory()->create();

        expect($space1->canCombineWith($space2))->toBeFalse();
    });

    it('combined spaces reference their component spaces', function () {
        $universe = Space::where('slug', 'the-universe')->first();
        $glow = Space::where('slug', 'the-glow')->first();
        $ray = Space::where('slug', 'the-ray')->first();

        expect($universe->can_combine_with)->toBeArray()
            ->and($universe->can_combine_with)->toContain($glow->id, $ray->id);
    });
});

describe('Space availability', function () {
    it('isAvailable uses BookingValidationService', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        $result = $space->isAvailable($start->toDateTime(), $end->toDateTime());

        expect($result)->toBeTrue();
    });

    it('isAvailable returns false when space is booked', function () {
        $space = Space::where('slug', 'the-glow')->first();
        $start = now()->addDays(10);
        $end = now()->addDays(12);

        // Create a conflicting booking
        $booking = Booking::factory()->confirmed()->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);

        $booking->spaces()->attach($space->id);

        $result = $space->isAvailable($start->toDateTime(), $end->toDateTime());

        expect($result)->toBeFalse();
    });
});

describe('Space scopes', function () {
    it('active scope returns only active spaces', function () {
        $activeCount = Space::where('is_active', true)->count();

        $results = Space::active()->get();

        expect($results)->toHaveCount($activeCount);
    });

    it('ofType scope filters by space type', function () {
        $conferenceRooms = Space::ofType(SpaceType::ConferenceRoom)->get();

        expect($conferenceRooms)->not->toHaveCount(0);

        foreach ($conferenceRooms as $room) {
            expect($room->type)->toBe(SpaceType::ConferenceRoom);
        }
    });
});

describe('Space types', function () {
    it('supports conference room type', function () {
        $space = Space::where('slug', 'the-glow')->first();

        expect($space->type)->toBe(SpaceType::ConferenceRoom);
    });

    it('supports accommodation type', function () {
        $space = Space::where('slug', 'the-sun')->first();

        expect($space->type)->toBe(SpaceType::Accommodation);
    });

    it('supports co-working type', function () {
        $space = Space::where('slug', 'co-working')->first();

        expect($space->type)->toBe(SpaceType::CoWorking);
    });

    it('supports therapy room type', function () {
        $space = Space::where('slug', 'the-light-center')->first();

        expect($space->type)->toBe(SpaceType::TherapyRoom);
    });

    it('supports combined type', function () {
        $space = Space::where('slug', 'the-universe')->first();

        expect($space->type)->toBe(SpaceType::Combined);
    });
});

describe('Space factory states', function () {
    it('can create conference room', function () {
        $space = Space::factory()->conferenceRoom()->create();

        expect($space->type)->toBe(SpaceType::ConferenceRoom)
            ->and($space->capacity)->toBeGreaterThanOrEqual(6)
            ->and($space->features)->toContain('WiFi', 'Projector');
    });

    it('can create accommodation space', function () {
        $space = Space::factory()->accommodation()->create();

        expect($space->type)->toBe(SpaceType::Accommodation)
            ->and($space->capacity)->toBe(2)
            ->and($space->features)->toContain('Queen Bed', 'Private Bathroom');
    });

    it('can create co-working space', function () {
        $space = Space::factory()->coWorking()->create();

        expect($space->type)->toBe(SpaceType::CoWorking)
            ->and($space->features)->toContain('High-Speed WiFi', 'Hot Desks');
    });

    it('can create therapy room', function () {
        $space = Space::factory()->therapyRoom()->create();

        expect($space->type)->toBe(SpaceType::TherapyRoom)
            ->and($space->capacity)->toBe(2)
            ->and($space->features)->toContain('Light Therapy Equipment');
    });

    it('can create inactive space', function () {
        $space = Space::factory()->inactive()->create();

        expect($space->is_active)->toBeFalse();
    });
});
