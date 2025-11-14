<?php

namespace App\Models;

use App\Services\BookingValidationService;
use App\SpaceType;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Space extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'capacity',
        'features',
        'can_combine_with',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => SpaceType::class,
            'features' => 'array',
            'can_combine_with' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_spaces')
            ->using(BookingSpace::class)
            ->withPivot(['duration_type', 'price'])
            ->withTimestamps();
    }

    public function canCombineWith(Space $space): bool
    {
        if (! $this->can_combine_with) {
            return false;
        }

        return in_array($space->id, $this->can_combine_with);
    }

    public function isAvailable(DateTime $start, DateTime $end): bool
    {
        $validationService = app(BookingValidationService::class);

        return $validationService->isSpaceAvailable(
            $this,
            Carbon::instance($start),
            Carbon::instance($end)
        );
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, SpaceType $type): void
    {
        $query->where('type', $type);
    }
}
