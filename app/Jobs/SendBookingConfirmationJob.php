<?php

namespace App\Jobs;

use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function handle(): void
    {
        try {
            Mail::to($this->booking->customer->email)
                ->send(new BookingConfirmationMail($this->booking));

            Log::info('Booking confirmation email sent', [
                'booking_id' => $this->booking->id,
                'customer_email' => $this->booking->customer->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking confirmation email', [
                'booking_id' => $this->booking->id,
                'customer_email' => $this->booking->customer->email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
