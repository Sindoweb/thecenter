<?php

namespace App\Models;

use App\BookingType;
use App\DurationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'booking_type',
        'duration_type',
        'base_duration_type',
        'price',
        'discount_percentage',
        'min_people',
        'max_people',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'booking_type' => BookingType::class,
            'duration_type' => DurationType::class,
            'base_duration_type' => DurationType::class,
            'price' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeForBooking(Builder $query, BookingType $bookingType, DurationType $durationType): void
    {
        $query->where('booking_type', $bookingType)
            ->where('duration_type', $durationType);
    }

    public function scopeValidOn(Builder $query, $date): void
    {
        $query->where(function ($q) use ($date) {
            $q->whereNull('valid_from')
                ->orWhere('valid_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('valid_until')
                ->orWhere('valid_until', '>=', $date);
        });
    }

    public function getDiscountedPrice(): float
    {
        if (! $this->discount_percentage) {
            return (float) $this->price;
        }

        $discount = $this->price * ($this->discount_percentage / 100);

        return (float) ($this->price - $discount);
    }
}
