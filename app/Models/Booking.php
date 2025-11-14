<?php

namespace App\Models;

use App\BookingStatus;
use App\BookingType;
use App\DurationType;
use App\PaymentStatus;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'space_id',
        'booking_type',
        'duration_type',
        'price',
        'status',
        'payment_status',
        'start_date',
        'end_date',
        'total_price',
        'discount_amount',
        'final_price',
        'number_of_people',
        'special_requests',
        'internal_notes',
        'cancelled_at',
        'cancellation_reason',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_type' => BookingType::class,
            'duration_type' => DurationType::class,
            'status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_price' => 'decimal:2',
            'number_of_people' => 'integer',
            'cancelled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * @deprecated Use space() relationship instead. Kept for backwards compatibility with pivot table.
     */
    public function spaces(): BelongsToMany
    {
        return $this->belongsToMany(Space::class, 'booking_spaces')
            ->using(BookingSpace::class)
            ->withPivot(['duration_type', 'price'])
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptionUsage(): HasOne
    {
        return $this->hasOne(SubscriptionUsage::class);
    }

    public function scopeForDateRange(Builder $query, $start, $end): void
    {
        $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('start_date', [$start, $end])
                ->orWhereBetween('end_date', [$start, $end])
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $end);
                });
        });
    }

    public function scopeByStatus(Builder $query, BookingStatus $status): void
    {
        $query->where('status', $status);
    }

    public function scopeUpcoming(Builder $query): void
    {
        $query->where('start_date', '>', now())
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed]);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', BookingStatus::Pending);
    }

    public function confirm(): bool
    {
        $this->status = BookingStatus::Confirmed;

        return $this->save();
    }

    public function cancel(?string $reason = null): bool
    {
        $this->status = BookingStatus::Cancelled;
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;

        return $this->save();
    }

    public function isOverlapping(DateTime $start, DateTime $end): bool
    {
        return $this->start_date < $end && $this->end_date > $start;
    }

    public function getDuration(): int
    {
        return $this->start_date->diffInDays($this->end_date);
    }
}
