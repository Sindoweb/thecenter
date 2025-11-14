<?php

declare(strict_types=1);

use App\BookingType;
use App\DurationType;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SubscriptionService::class);
});

describe('createSubscription', function () {
    it('creates subscription record', function () {
        $customer = Customer::factory()->withMollieCustomer()->create();

        $data = [
            'booking_type' => BookingType::CoWorking,
            'duration_type' => DurationType::Monthly,
            'quantity' => 1,
            'price' => 300.00,
            'usage_limit' => 20,
            'mollie_plan_id' => 'plan_test',
        ];

        $subscription = $this->service->createSubscription($customer, $data);

        expect($subscription)->toBeInstanceOf(Subscription::class)
            ->and($subscription->customer_id)->toBe($customer->id)
            ->and($subscription->booking_type)->toBe(BookingType::CoWorking)
            ->and($subscription->price)->toBe('300.00')
            ->and($subscription->usage_count)->toBe(0);
    })->skip('Requires Cashier mock setup');
});

describe('cancelSubscription', function () {
    it('cancels subscription locally', function () {
        $subscription = Subscription::factory()->active()->create();

        $this->service->cancelSubscription($subscription);

        expect($subscription->fresh()->cancelled_at)->not->toBeNull();
    })->skip('Requires Cashier mock setup');
});

describe('recordUsage', function () {
    it('creates subscription usage record', function () {
        $subscription = Subscription::factory()->active()->create([
            'usage_limit' => 10,
            'usage_count' => 0,
        ]);

        $usage = $this->service->recordUsage($subscription);

        expect($usage)->toBeInstanceOf(SubscriptionUsage::class)
            ->and($usage->subscription_id)->toBe($subscription->id)
            ->and($usage->used_at)->not->toBeNull();
    });

    it('increments subscription usage count', function () {
        $subscription = Subscription::factory()->active()->create([
            'usage_limit' => 10,
            'usage_count' => 3,
        ]);

        $this->service->recordUsage($subscription);

        expect($subscription->fresh()->usage_count)->toBe(4);
    });

    it('can link usage to a booking', function () {
        $subscription = Subscription::factory()->active()->create([
            'usage_limit' => 10,
            'usage_count' => 0,
        ]);

        $booking = Booking::factory()->create();

        $usage = $this->service->recordUsage($subscription, $booking);

        expect($usage->booking_id)->toBe($booking->id);
    });

    it('throws exception when usage limit exceeded', function () {
        $subscription = Subscription::factory()->active()->create([
            'usage_limit' => 10,
            'usage_count' => 10,
        ]);

        expect(fn () => $this->service->recordUsage($subscription))
            ->toThrow(\RuntimeException::class, 'usage limit exceeded');
    });

    it('allows usage for unlimited subscriptions', function () {
        $subscription = Subscription::factory()->unlimited()->active()->create([
            'usage_count' => 100,
        ]);

        $usage = $this->service->recordUsage($subscription);

        expect($usage)->toBeInstanceOf(SubscriptionUsage::class)
            ->and($subscription->fresh()->usage_count)->toBe(101);
    });
});

describe('hasUsageRemaining', function () {
    it('returns true when usage below limit', function () {
        $subscription = Subscription::factory()->create([
            'usage_limit' => 10,
            'usage_count' => 5,
        ]);

        $result = $this->service->hasUsageRemaining($subscription);

        expect($result)->toBeTrue();
    });

    it('returns false when usage at limit', function () {
        $subscription = Subscription::factory()->create([
            'usage_limit' => 10,
            'usage_count' => 10,
        ]);

        $result = $this->service->hasUsageRemaining($subscription);

        expect($result)->toBeFalse();
    });

    it('returns true for unlimited subscriptions', function () {
        $subscription = Subscription::factory()->unlimited()->create([
            'usage_count' => 999,
        ]);

        $result = $this->service->hasUsageRemaining($subscription);

        expect($result)->toBeTrue();
    });
});

describe('getAvailableUsage', function () {
    it('returns correct remaining usage', function () {
        $subscription = Subscription::factory()->create([
            'usage_limit' => 10,
            'usage_count' => 3,
        ]);

        $available = $this->service->getAvailableUsage($subscription);

        expect($available)->toBe(7);
    });

    it('returns null for unlimited subscriptions', function () {
        $subscription = Subscription::factory()->unlimited()->create();

        $available = $this->service->getAvailableUsage($subscription);

        expect($available)->toBeNull();
    });

    it('returns zero when limit is exceeded', function () {
        $subscription = Subscription::factory()->create([
            'usage_limit' => 10,
            'usage_count' => 12,
        ]);

        $available = $this->service->getAvailableUsage($subscription);

        expect($available)->toBe(0);
    });
});

describe('resumeSubscription', function () {
    it('clears cancelled_at timestamp', function () {
        $subscription = Subscription::factory()->cancelled()->create();

        $this->service->resumeSubscription($subscription);

        expect($subscription->fresh()->cancelled_at)->toBeNull();
    })->skip('Requires Cashier mock setup');

    it('throws exception for non-cancelled subscription', function () {
        $subscription = Subscription::factory()->active()->create();

        expect(fn () => $this->service->resumeSubscription($subscription))
            ->toThrow(\InvalidArgumentException::class, 'not cancelled');
    })->skip('Requires Cashier mock setup');
});

describe('renewSubscription', function () {
    it('updates subscription dates', function () {
        $subscription = Subscription::factory()->create([
            'starts_at' => now()->subMonth(),
            'ends_at' => now(),
            'duration_type' => DurationType::Monthly,
            'usage_count' => 5,
        ]);

        $renewed = $this->service->renewSubscription($subscription);

        expect($renewed->starts_at->toDateString())->toBe(now()->toDateString())
            ->and($renewed->ends_at->toDateString())->toBe(now()->addMonth()->toDateString());
    });

    it('resets usage count', function () {
        $subscription = Subscription::factory()->create([
            'starts_at' => now()->subMonth(),
            'ends_at' => now(),
            'usage_count' => 8,
        ]);

        $renewed = $this->service->renewSubscription($subscription);

        expect($renewed->usage_count)->toBe(0);
    });
});
