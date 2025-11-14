<?php

namespace App\Models;

use App\PaymentStatus;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'subscription_id',
        'customer_id',
        'amount',
        'status',
        'payment_method',
        'transaction_id',
        'refund_amount',
        'paid_at',
        'refunded_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'refund_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopePaid(Builder $query): void
    {
        $query->where('status', PaymentStatus::Paid);
    }

    public function scopeByStatus(Builder $query, PaymentStatus $status): void
    {
        $query->where('status', $status);
    }

    public function markAsPaid(?DateTime $paidAt = null): bool
    {
        $this->status = PaymentStatus::Paid;
        $this->paid_at = $paidAt ?? now();

        return $this->save();
    }

    public function refund(?float $amount = null): bool
    {
        $refundAmount = $amount ?? (float) $this->amount;

        if ($refundAmount > $this->amount) {
            return false;
        }

        $this->refund_amount = ($this->refund_amount ?? 0) + $refundAmount;

        if ($this->refund_amount >= $this->amount) {
            $this->status = PaymentStatus::Refunded;
        } else {
            $this->status = PaymentStatus::PartiallyRefunded;
        }

        $this->refunded_at = now();

        return $this->save();
    }
}
