<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Space;
use App\Services\CalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(CalendarService::class);
});

describe('generateIcs', function () {
    it('returns valid iCalendar format', function () {
        $space = Space::factory()->conferenceRoom()->create(['name' => 'Test Room']);
        $booking = Booking::factory()->create([
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
            'number_of_people' => 5,
        ]);

        $booking->spaces()->attach($space->id);

        $ics = $this->service->generateIcs($booking);

        expect($ics)->toContain('BEGIN:VCALENDAR')
            ->and($ics)->toContain('END:VCALENDAR')
            ->and($ics)->toContain('BEGIN:VEVENT')
            ->and($ics)->toContain('END:VEVENT')
            ->and($ics)->toContain('VERSION:2.0');
    });

    it('includes correct event details', function () {
        $space = Space::factory()->conferenceRoom()->create(['name' => 'Test Room']);
        $booking = Booking::factory()->create([
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
            'number_of_people' => 5,
        ]);

        $booking->spaces()->attach($space->id);

        $ics = $this->service->generateIcs($booking);

        expect($ics)->toContain('Test Room')
            ->and($ics)->toContain('Guests: 5')
            ->and($ics)->toContain('SUMMARY:Booking at The Center - Test Room');
    });

    it('includes location', function () {
        $space = Space::factory()->create(['name' => 'Test Room']);
        $booking = Booking::factory()->create();
        $booking->spaces()->attach($space->id);

        $ics = $this->service->generateIcs($booking);

        expect($ics)->toContain('LOCATION:');
    });

    it('includes description with booking details', function () {
        $space = Space::factory()->create(['name' => 'Test Room']);
        $booking = Booking::factory()->create([
            'number_of_people' => 8,
            'special_requests' => 'Please prepare projector',
        ]);
        $booking->spaces()->attach($space->id);

        $ics = $this->service->generateIcs($booking);

        expect($ics)->toContain('DESCRIPTION:')
            ->and($ics)->toContain('Guests: 8')
            ->and($ics)->toContain('Special Requests:');
    });

    it('handles multiple spaces', function () {
        $space1 = Space::factory()->create(['name' => 'Room A']);
        $space2 = Space::factory()->create(['name' => 'Room B']);
        $booking = Booking::factory()->create();

        $booking->spaces()->attach([$space1->id, $space2->id]);

        $ics = $this->service->generateIcs($booking);

        expect($ics)->toContain('Room A')
            ->and($ics)->toContain('Room B');
    });

    it('includes unique identifier', function () {
        $booking = Booking::factory()->create();
        $space = Space::factory()->create();
        $booking->spaces()->attach($space->id);

        $ics = $this->service->generateIcs($booking);

        expect($ics)->toContain("UID:booking-{$booking->id}@thecenter.test");
    });

    it('uses correct date format', function () {
        $space = Space::factory()->create();
        $booking = Booking::factory()->create([
            'start_date' => '2025-06-15 14:00:00',
            'end_date' => '2025-06-15 16:00:00',
        ]);
        $booking->spaces()->attach($space->id);

        $ics = $this->service->generateIcs($booking);

        expect($ics)->toContain('DTSTART:20250615T')
            ->and($ics)->toContain('DTEND:20250615T');
    });
});
