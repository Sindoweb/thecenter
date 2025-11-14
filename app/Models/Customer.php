<?php

namespace App\Models;

use App\BookingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;

class Customer extends Model
{
    use Billable, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'vat_number',
        'address',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function hasActiveSubscription(?BookingType $type = null): bool
    {
        $query = $this->subscriptions()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereNull('cancelled_at');

        if ($type) {
            $query->where('booking_type', $type);
        }

        return $query->exists();
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getActiveSubscriptions(): Collection
    {
        return $this->subscriptions()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereNull('cancelled_at')
            ->get();
    }
}
