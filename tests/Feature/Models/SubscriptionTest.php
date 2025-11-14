<?php

declare(strict_types=1);

use App\BookingType;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Subscription model', function () {
    it('can create a subscription', function () {
        $customer = Customer::factory()->create();

        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'booking_type' => BookingType::CoWorking,
            'price' => 300.00,
        ]);

        expect($subscription->customer_id)->toBe($customer->id)
            ->and($subscription->booking_type)->toBe(BookingType::CoWorking)
            ->and($subscription->price)->toBe('300.00');
    });

    it('has correct relationships', function () {
        $subscription = Subscription::factory()->create();

        SubscriptionUsage::factory()->count(3)->create([
            'subscription_id' => $subscription->id,
        ]);

        Payment::factory()->count(2)->create([
            'subscription_id' => $subscription->id,
        ]);

        expect($subscription->customer)->toBeInstanceOf(Customer::class)
            ->and($subscription->usages)->toHaveCount(3)
            ->and($subscription->payments)->toHaveCount(2);
    });

    it('casts dates correctly', function () {
        $subscription = Subscription::factory()->create();

        expect($subscription->starts_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($subscription->ends_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });
});

describe('Subscription active scope', function () {
    it('returns only active subscriptions', function () {
        // Active subscription
        Subscription::factory()->active()->create();

        // Expired subscription
        Subscription::factory()->expired()->create();

        // Cancelled subscription
        Subscription::factory()->cancelled()->create([
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->addWeek(),
        ]);

        $activeSubscriptions = Subscription::active()->get();

        expect($activeSubscriptions)->toHaveCount(1);
    });
});

describe('Subscription status methods', function () {
    it('isActive returns true for active subscription', function () {
        $subscription = Subscription::factory()->active()->create();

        expect($subscription->isActive())->toBeTrue();
    });

    it('isActive returns false for expired subscription', function () {
        $subscription = Subscription::factory()->expired()->create();

        expect($subscription->isActive())->toBeFalse();
    });

    it('isActive returns false for cancelled subscription', function () {
        $subscription = Subscription::factory()->cancelled()->create([
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->addWeek(),
        ]);

        expect($subscription->isActive())->toBeFalse();
    });

    it('isActive returns false for future subscription', function () {
        $subscription = Subscription::factory()->create([
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addMonths(2),
        ]);

        expect($subscription->isActive())->toBeFalse();
    });
});

describe('Subscription usage management', function () {
    it('hasUsageRemaining returns true when usage below limit', function () {
        $subscription = Subscription::factory()->create([
            'usage_limit' => 10,
            'usage_count' => 5,
        ]);

        expect($subscription->hasUsageRemaining())->toBeTrue();
    });

    it('hasUsageRemaining returns false when usage at limit', function () {
        $subscription = Subscription::factory()->create([
            'usage_limit' => 10,
            'usage_count' => 10,
        ]);

        expect($subscription->hasUsageRemaining())->toBeFalse();
    });

    it('hasUsageRemaining returns false when usage exceeds limit', function () {
        $subscription = Subscription::factory()->create([
            'usage_limit' => 10,
            'usage_count' => 11,
        ]);

        expect($subscription->hasUsageRemaining())->toBeFalse();
    });

    it('hasUsageRemaining returns true when no usage limit', function () {
        $subscription = Subscription::factory()->unlimited()->create([
            'usage_count' => 1000,
        ]);

        expect($subscription->hasUsageRemaining())->toBeTrue();
    });

    it('incrementUsage increases usage count', function () {
        $subscription = Subscription::factory()->create([
            'usage_count' => 5,
        ]);

        $result = $subscription->incrementUsage();

        expect($result)->toBeTrue()
            ->and($subscription->fresh()->usage_count)->toBe(6);
    });

    it('getRemainingUsage returns correct count', function () {
        $subscription = Subscription::factory()->create([
            'usage_limit' => 10,
            'usage_count' => 3,
        ]);

        expect($subscription->getRemainingUsage())->toBe(7);
    });

    it('getRemainingUsage returns max int when no limit', function () {
        $subscription = Subscription::factory()->unlimited()->create();

        expect($subscription->getRemainingUsage())->toBe(PHP_INT_MAX);
    });

    it('getRemainingUsage returns zero when usage exceeds limit', function () {
        $subscription = Subscription::factory()->create([
            'usage_limit' => 10,
            'usage_count' => 12,
        ]);

        expect($subscription->getRemainingUsage())->toBe(0);
    });
});

describe('Subscription cancellation', function () {
    it('cancel sets cancelled_at timestamp', function () {
        $subscription = Subscription::factory()->active()->create();

        expect($subscription->cancelled_at)->toBeNull();

        $result = $subscription->cancel();

        expect($result)->toBeTrue()
            ->and($subscription->fresh()->cancelled_at)->not->toBeNull();
    });

    it('cancelled subscription is not considered active', function () {
        $subscription = Subscription::factory()->active()->create();

        $subscription->cancel();

        expect($subscription->fresh()->isActive())->toBeFalse();
    });
});

describe('Subscription factory states', function () {
    it('can create active subscription', function () {
        $subscription = Subscription::factory()->active()->create();

        expect($subscription->isActive())->toBeTrue();
    });

    it('can create cancelled subscription', function () {
        $subscription = Subscription::factory()->cancelled()->create();

        expect($subscription->cancelled_at)->not->toBeNull();
    });

    it('can create expired subscription', function () {
        $subscription = Subscription::factory()->expired()->create();

        expect($subscription->isActive())->toBeFalse()
            ->and($subscription->ends_at)->toBeLessThan(now());
    });

    it('can create subscription with specific usage', function () {
        $subscription = Subscription::factory()->withUsage(7)->create();

        expect($subscription->usage_count)->toBe(7);
    });

    it('can create unlimited subscription', function () {
        $subscription = Subscription::factory()->unlimited()->create();

        expect($subscription->usage_limit)->toBeNull()
            ->and($subscription->hasUsageRemaining())->toBeTrue();
    });

    it('can create co-working subscription', function () {
        $subscription = Subscription::factory()->coWorking()->create();

        expect($subscription->booking_type)->toBe(BookingType::CoWorking)
            ->and($subscription->price)->toBe('300.00')
            ->and($subscription->usage_limit)->toBeNull();
    });

    it('can create light therapy subscription', function () {
        $subscription = Subscription::factory()->lightTherapy()->create();

        expect($subscription->booking_type)->toBe(BookingType::LightTherapy)
            ->and($subscription->price)->toBe('400.00')
            ->and($subscription->usage_limit)->toBe(4);
    });
});
