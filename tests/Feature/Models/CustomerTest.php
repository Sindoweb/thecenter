<?php

declare(strict_types=1);

use App\BookingType;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Customer model', function () {
    it('can create a customer', function () {
        $customer = Customer::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        expect($customer->name)->toBe('John Doe')
            ->and($customer->email)->toBe('john@example.com')
            ->and($customer->is_active)->toBeTrue();
    });

    it('has Billable trait', function () {
        $customer = Customer::factory()->create();

        expect($customer)->toHaveMethod('charge')
            ->and($customer)->toHaveMethod('createAsMollieCustomer');
    });

    it('has correct relationships', function () {
        $customer = Customer::factory()->create();

        Booking::factory()->count(3)->create(['customer_id' => $customer->id]);
        Subscription::factory()->count(2)->create(['customer_id' => $customer->id]);
        Payment::factory()->count(5)->create(['customer_id' => $customer->id]);

        expect($customer->bookings)->toHaveCount(3)
            ->and($customer->subscriptions)->toHaveCount(2)
            ->and($customer->payments)->toHaveCount(5);
    });

    it('soft deletes correctly', function () {
        $customer = Customer::factory()->create();
        $customerId = $customer->id;

        $customer->delete();

        expect(Customer::find($customerId))->toBeNull()
            ->and(Customer::withTrashed()->find($customerId))->not->toBeNull();
    });
});

describe('Customer scopes', function () {
    it('active scope returns only active customers', function () {
        Customer::factory()->count(3)->create(['is_active' => true]);
        Customer::factory()->count(2)->inactive()->create();

        $activeCustomers = Customer::active()->get();

        expect($activeCustomers)->toHaveCount(3);
    });
});

describe('Customer subscription methods', function () {
    it('hasActiveSubscription returns true when customer has active subscription', function () {
        $customer = Customer::factory()->create();

        Subscription::factory()->active()->create([
            'customer_id' => $customer->id,
            'booking_type' => BookingType::CoWorking,
        ]);

        expect($customer->hasActiveSubscription())->toBeTrue();
    });

    it('hasActiveSubscription returns false when no active subscriptions', function () {
        $customer = Customer::factory()->create();

        Subscription::factory()->expired()->create([
            'customer_id' => $customer->id,
        ]);

        expect($customer->hasActiveSubscription())->toBeFalse();
    });

    it('hasActiveSubscription can filter by booking type', function () {
        $customer = Customer::factory()->create();

        Subscription::factory()->active()->coWorking()->create([
            'customer_id' => $customer->id,
        ]);

        expect($customer->hasActiveSubscription(BookingType::CoWorking))->toBeTrue()
            ->and($customer->hasActiveSubscription(BookingType::LightTherapy))->toBeFalse();
    });

    it('hasActiveSubscription returns false for cancelled subscriptions', function () {
        $customer = Customer::factory()->create();

        Subscription::factory()->cancelled()->create([
            'customer_id' => $customer->id,
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->addWeek(),
        ]);

        expect($customer->hasActiveSubscription())->toBeFalse();
    });

    it('getActiveSubscriptions returns all active subscriptions', function () {
        $customer = Customer::factory()->create();

        Subscription::factory()->active()->coWorking()->create([
            'customer_id' => $customer->id,
        ]);

        Subscription::factory()->active()->lightTherapy()->create([
            'customer_id' => $customer->id,
        ]);

        Subscription::factory()->expired()->create([
            'customer_id' => $customer->id,
        ]);

        $activeSubscriptions = $customer->getActiveSubscriptions();

        expect($activeSubscriptions)->toHaveCount(2);
    });

    it('getActiveSubscriptions returns empty collection when no active subscriptions', function () {
        $customer = Customer::factory()->create();

        Subscription::factory()->expired()->create([
            'customer_id' => $customer->id,
        ]);

        Subscription::factory()->cancelled()->create([
            'customer_id' => $customer->id,
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->addWeek(),
        ]);

        $activeSubscriptions = $customer->getActiveSubscriptions();

        expect($activeSubscriptions)->toHaveCount(0);
    });
});

describe('Customer factory states', function () {
    it('can create inactive customer', function () {
        $customer = Customer::factory()->inactive()->create();

        expect($customer->is_active)->toBeFalse();
    });

    it('can create customer with Mollie customer ID', function () {
        $customer = Customer::factory()->withMollieCustomer()->create();

        expect($customer->mollie_customer_id)->not->toBeNull()
            ->and($customer->mollie_customer_id)->toStartWith('cst_');
    });
});
