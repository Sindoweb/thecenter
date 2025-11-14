<?php

namespace App\Models;

use App\BookingType;
use App\DurationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'booking_type',
        'duration_type',
        'quantity',
        'price',
        'usage_limit',
        'usage_count',
        'starts_at',
        'ends_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_type' => BookingType::class,
            'duration_type' => DurationType::class,
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereNull('cancelled_at');
    }

    public function isActive(): bool
    {
        if ($this->cancelled_at) {
            return false;
        }

        return $this->starts_at <= now() && $this->ends_at >= now();
    }

    public function hasUsageRemaining(): bool
    {
        if (! $this->usage_limit) {
            return true;
        }

        return $this->usage_count < $this->usage_limit;
    }

    public function incrementUsage(): bool
    {
        $this->usage_count++;

        return $this->save();
    }

    public function cancel(): bool
    {
        $this->cancelled_at = now();

        return $this->save();
    }

    public function getRemainingUsage(): int
    {
        if (! $this->usage_limit) {
            return PHP_INT_MAX;
        }

        return max(0, $this->usage_limit - $this->usage_count);
    }
}
