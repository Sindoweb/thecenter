<?php

namespace App\Services;

use App\BookingType;
use App\DurationType;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Create a recurring subscription for a customer
     */
    public function createSubscription(Customer $customer, array $data): Subscription
    {
        return DB::transaction(function () use ($customer, $data) {
            // Ensure customer has Mollie customer ID
            if (! $customer->mollie_customer_id) {
                $customer->createAsMollieCustomer();
            }

            // Extract subscription details
            $bookingType = $data['booking_type'] instanceof BookingType
                ? $data['booking_type']
                : BookingType::from($data['booking_type']);

            $durationType = $data['duration_type'] instanceof DurationType
                ? $data['duration_type']
                : DurationType::from($data['duration_type']);

            $quantity = $data['quantity'] ?? 1;
            $price = $data['price'];
            $usageLimit = $data['usage_limit'] ?? null;

            // Generate subscription name based on type
            $subscriptionName = $this->generateSubscriptionName($bookingType, $durationType);

            // Calculate dates based on duration type
            $startsAt = $data['starts_at'] ?? now();
            $endsAt = $this->calculateEndDate($startsAt, $durationType);

            // Create Mollie subscription using Cashier
            $mollieSubscription = $customer->newSubscription($subscriptionName, $data['mollie_plan_id'] ?? 'plan_default')
                ->quantity($quantity)
                ->create();

            // Create subscription record in our database
            $subscription = Subscription::create([
                'customer_id' => $customer->id,
                'booking_type' => $bookingType,
                'duration_type' => $durationType,
                'quantity' => $quantity,
                'price' => $price,
                'usage_limit' => $usageLimit,
                'usage_count' => 0,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            Log::info('Subscription created', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id,
                'booking_type' => $bookingType->value,
                'duration_type' => $durationType->value,
                'mollie_subscription' => $mollieSubscription->name,
            ]);

            return $subscription;
        });
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Subscription $subscription): void
    {
        DB::transaction(function () use ($subscription) {
            // Cancel subscription on Mollie via Cashier
            $customer = $subscription->customer;
            $subscriptionName = $this->generateSubscriptionName(
                $subscription->booking_type,
                $subscription->duration_type
            );

            // Find Cashier subscription and cancel it
            $cashierSubscription = $customer->subscription($subscriptionName);
            if ($cashierSubscription) {
                $cashierSubscription->cancel();

                Log::info('Mollie subscription cancelled via Cashier', [
                    'subscription_id' => $subscription->id,
                    'cashier_subscription' => $subscriptionName,
                ]);
            }

            // Update our subscription record
            $subscription->cancel();

            Log::info('Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
                'cancelled_at' => $subscription->cancelled_at->toIso8601String(),
            ]);
        });
    }

    /**
     * Record usage of a subscription
     */
    public function recordUsage(Subscription $subscription, ?Booking $booking = null): SubscriptionUsage
    {
        return DB::transaction(function () use ($subscription, $booking) {
            // Check if subscription has usage remaining
            if (! $subscription->hasUsageRemaining()) {
                throw new \RuntimeException('Subscription usage limit exceeded');
            }

            // Create usage record
            $usage = SubscriptionUsage::create([
                'subscription_id' => $subscription->id,
                'booking_id' => $booking?->id,
                'used_at' => now(),
            ]);

            // Increment subscription usage count
            $subscription->incrementUsage();

            Log::info('Subscription usage recorded', [
                'subscription_id' => $subscription->id,
                'usage_id' => $usage->id,
                'booking_id' => $booking?->id,
                'usage_count' => $subscription->usage_count,
                'usage_limit' => $subscription->usage_limit,
            ]);

            return $usage;
        });
    }

    /**
     * Check if subscription has usage remaining
     */
    public function hasUsageRemaining(Subscription $subscription): bool
    {
        return $subscription->hasUsageRemaining();
    }

    /**
     * Get available usage for a subscription
     */
    public function getAvailableUsage(Subscription $subscription): ?int
    {
        if (! $subscription->usage_limit) {
            return null; // Unlimited usage
        }

        $remainingUsage = $subscription->getRemainingUsage();

        return max(0, $remainingUsage);
    }

    /**
     * Resume a cancelled subscription
     */
    public function resumeSubscription(Subscription $subscription): void
    {
        DB::transaction(function () use ($subscription) {
            if (! $subscription->cancelled_at) {
                throw new \InvalidArgumentException('Subscription is not cancelled');
            }

            // Resume subscription on Mollie via Cashier
            $customer = $subscription->customer;
            $subscriptionName = $this->generateSubscriptionName(
                $subscription->booking_type,
                $subscription->duration_type
            );

            $cashierSubscription = $customer->subscription($subscriptionName);
            if ($cashierSubscription && $cashierSubscription->cancelled()) {
                $cashierSubscription->resume();

                Log::info('Mollie subscription resumed via Cashier', [
                    'subscription_id' => $subscription->id,
                    'cashier_subscription' => $subscriptionName,
                ]);
            }

            // Clear cancellation timestamp
            $subscription->cancelled_at = null;
            $subscription->save();

            Log::info('Subscription resumed', [
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
            ]);
        });
    }

    /**
     * Renew a subscription for another period
     */
    public function renewSubscription(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            // Calculate new dates
            $startsAt = $subscription->ends_at;
            $endsAt = $this->calculateEndDate($startsAt, $subscription->duration_type);

            // Update subscription dates
            $subscription->starts_at = $startsAt;
            $subscription->ends_at = $endsAt;
            $subscription->usage_count = 0; // Reset usage count for new period
            $subscription->save();

            Log::info('Subscription renewed', [
                'subscription_id' => $subscription->id,
                'new_starts_at' => $startsAt->toIso8601String(),
                'new_ends_at' => $endsAt->toIso8601String(),
            ]);

            return $subscription;
        });
    }

    /**
     * Generate a subscription name based on booking type and duration
     */
    protected function generateSubscriptionName(BookingType $bookingType, DurationType $durationType): string
    {
        return strtolower($bookingType->value).'_'.strtolower($durationType->value);
    }

    /**
     * Calculate end date based on duration type
     */
    protected function calculateEndDate(\DateTimeInterface $startDate, DurationType $durationType): \DateTime
    {
        $date = new \DateTime($startDate->format('Y-m-d H:i:s'));

        return match ($durationType) {
            DurationType::HalfDay => $date->modify('+12 hours'),
            DurationType::FullDay, DurationType::DayPass => $date->modify('+1 day'),
            DurationType::Night => $date->modify('+1 day'),
            DurationType::Session => $date->modify('+2 hours'),
            DurationType::Weekly => $date->modify('+1 week'),
            DurationType::Monthly => $date->modify('+1 month'),
            DurationType::Quarterly => $date->modify('+3 months'),
        };
    }
}
