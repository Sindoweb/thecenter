<?php

namespace App\Models;

use App\DurationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BookingSpace extends Pivot
{
    use HasFactory;

    protected $table = 'booking_spaces';

    protected $fillable = [
        'booking_id',
        'space_id',
        'duration_type',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'duration_type' => DurationType::class,
            'price' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }
}
