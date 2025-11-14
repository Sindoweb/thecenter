<?php

namespace App\Observers;

use App\Models\Booking;

class BookingObserver
{
    /**
     * Handle the Booking "creating" event.
     */
    public function creating(Booking $booking): void
    {
        $this->setTotalPrice($booking);
    }

    /**
     * Handle the Booking "updating" event.
     */
    public function updating(Booking $booking): void
    {
        $this->setTotalPrice($booking);
    }

    /**
     * Set total_price to equal price (before discount).
     */
    protected function setTotalPrice(Booking $booking): void
    {
        if ($booking->price !== null && $booking->total_price === null) {
            $booking->total_price = $booking->price;
        }
    }
}
