<?php

declare(strict_types=1);

use App\BookingStatus;
use App\BookingType;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Space;
use App\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Booking model', function () {
    it('can create a booking with customer and spaces', function () {
        $customer = Customer::factory()->create();
        $space = Space::factory()->conferenceRoom()->create();

        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $booking->spaces()->attach($space->id, [
            'duration_type' => 'full_day',
            'price' => 500.00,
        ]);

        expect($booking->customer)->toBeInstanceOf(Customer::class)
            ->and($booking->customer->id)->toBe($customer->id)
            ->and($booking->spaces)->toHaveCount(1)
            ->and($booking->spaces->first()->id)->toBe($space->id);
    });

    it('can confirm a booking', function () {
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Pending,
        ]);

        expect($booking->status)->toBe(BookingStatus::Pending);

        $result = $booking->confirm();

        expect($result)->toBeTrue()
            ->and($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
    });

    it('can cancel a booking with reason', function () {
        $booking = Booking::factory()->confirmed()->create();

        expect($booking->status)->toBe(BookingStatus::Confirmed)
            ->and($booking->cancelled_at)->toBeNull();

        $reason = 'Customer requested cancellation';
        $result = $booking->cancel($reason);

        expect($result)->toBeTrue()
            ->and($booking->fresh()->status)->toBe(BookingStatus::Cancelled)
            ->and($booking->fresh()->cancelled_at)->not->toBeNull()
            ->and($booking->fresh()->cancellation_reason)->toBe($reason);
    });

    it('can cancel a booking without reason', function () {
        $booking = Booking::factory()->confirmed()->create();

        $result = $booking->cancel();

        expect($result)->toBeTrue()
            ->and($booking->fresh()->status)->toBe(BookingStatus::Cancelled)
            ->and($booking->fresh()->cancelled_at)->not->toBeNull()
            ->and($booking->fresh()->cancellation_reason)->toBeNull();
    });

    it('has correct relationships', function () {
        $customer = Customer::factory()->create();
        $space = Space::factory()->create();

        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $booking->spaces()->attach($space->id);

        expect($booking->customer)->toBeInstanceOf(Customer::class)
            ->and($booking->spaces)->toHaveCount(1)
            ->and($booking->payments)->toHaveCount(0);
    });

    it('soft deletes correctly', function () {
        $booking = Booking::factory()->create();
        $bookingId = $booking->id;

        $booking->delete();

        expect(Booking::find($bookingId))->toBeNull()
            ->and(Booking::withTrashed()->find($bookingId))->not->toBeNull()
            ->and(Booking::withTrashed()->find($bookingId)->deleted_at)->not->toBeNull();
    });
});

describe('Booking scopes', function () {
    it('forDateRange scope finds overlapping bookings', function () {
        $startDate = now()->addDays(5);
        $endDate = now()->addDays(10);

        // Create booking that overlaps
        $overlapping = Booking::factory()->create([
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(12),
        ]);

        // Create booking outside range
        $outside = Booking::factory()->create([
            'start_date' => now()->addDays(15),
            'end_date' => now()->addDays(20),
        ]);

        $results = Booking::forDateRange($startDate, $endDate)->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($overlapping->id);
    });

    it('byStatus scope filters by status', function () {
        Booking::factory()->count(3)->create(['status' => BookingStatus::Pending]);
        Booking::factory()->count(2)->confirmed()->create();

        $pending = Booking::byStatus(BookingStatus::Pending)->get();
        $confirmed = Booking::byStatus(BookingStatus::Confirmed)->get();

        expect($pending)->toHaveCount(3)
            ->and($confirmed)->toHaveCount(2);
    });

    it('upcoming scope returns future confirmed or pending bookings', function () {
        // Future confirmed booking
        $future1 = Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
        ]);

        // Future pending booking
        $future2 = Booking::factory()->create([
            'status' => BookingStatus::Pending,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(12),
        ]);

        // Past booking
        Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(5),
        ]);

        // Cancelled booking
        Booking::factory()->cancelled()->create([
            'start_date' => now()->addDays(15),
            'end_date' => now()->addDays(17),
        ]);

        $upcoming = Booking::upcoming()->get();

        expect($upcoming)->toHaveCount(2)
            ->and($upcoming->pluck('id')->toArray())->toContain($future1->id, $future2->id);
    });

    it('pending scope returns only pending bookings', function () {
        Booking::factory()->count(3)->create(['status' => BookingStatus::Pending]);
        Booking::factory()->count(2)->confirmed()->create();

        $pending = Booking::pending()->get();

        expect($pending)->toHaveCount(3);
    });
});

describe('Booking methods', function () {
    it('getDuration returns correct number of days', function () {
        $booking = Booking::factory()->create([
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(4),
        ]);

        expect($booking->getDuration())->toBe(3);
    });

    it('isOverlapping detects overlapping date ranges', function () {
        $booking = Booking::factory()->create([
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
        ]);

        // Overlapping range
        $overlaps1 = $booking->isOverlapping(
            now()->addDays(7)->toDateTime(),
            now()->addDays(12)->toDateTime()
        );

        // Overlapping range (starts before, ends within)
        $overlaps2 = $booking->isOverlapping(
            now()->addDays(3)->toDateTime(),
            now()->addDays(7)->toDateTime()
        );

        // Non-overlapping range (completely before)
        $noOverlap1 = $booking->isOverlapping(
            now()->addDays(1)->toDateTime(),
            now()->addDays(4)->toDateTime()
        );

        // Non-overlapping range (completely after)
        $noOverlap2 = $booking->isOverlapping(
            now()->addDays(15)->toDateTime(),
            now()->addDays(20)->toDateTime()
        );

        expect($overlaps1)->toBeTrue()
            ->and($overlaps2)->toBeTrue()
            ->and($noOverlap1)->toBeFalse()
            ->and($noOverlap2)->toBeFalse();
    });
});

describe('Booking types and pricing', function () {
    it('calculates final price with discount', function () {
        $booking = Booking::factory()->create([
            'total_price' => 1000.00,
            'discount_amount' => 100.00,
            'final_price' => 900.00,
        ]);

        expect($booking->final_price)->toBe('900.00')
            ->and($booking->discount_amount)->toBe('100.00');
    });

    it('supports different booking types', function () {
        $conference = Booking::factory()->conference()->create();
        $accommodation = Booking::factory()->accommodation()->create();
        $coWorking = Booking::factory()->coWorking()->create();
        $lightTherapy = Booking::factory()->lightTherapy()->create();

        expect($conference->booking_type)->toBe(BookingType::Conferentie)
            ->and($accommodation->booking_type)->toBe(BookingType::Accommodation)
            ->and($coWorking->booking_type)->toBe(BookingType::CoWorking)
            ->and($lightTherapy->booking_type)->toBe(BookingType::LightTherapy);
    });

    it('supports different payment statuses', function () {
        $pending = Booking::factory()->create(['payment_status' => PaymentStatus::Pending]);
        $paid = Booking::factory()->create(['payment_status' => PaymentStatus::Paid]);
        $failed = Booking::factory()->create(['payment_status' => PaymentStatus::Failed]);

        expect($pending->payment_status)->toBe(PaymentStatus::Pending)
            ->and($paid->payment_status)->toBe(PaymentStatus::Paid)
            ->and($failed->payment_status)->toBe(PaymentStatus::Failed);
    });
});
