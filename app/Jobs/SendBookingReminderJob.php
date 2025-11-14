<?php

namespace App\Jobs;

use App\Mail\BookingReminderMail;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function handle(): void
    {
        if ($this->booking->reminder_sent_at) {
            Log::info('Booking reminder already sent, skipping', [
                'booking_id' => $this->booking->id,
            ]);

            return;
        }

        try {
            Mail::to($this->booking->customer->email)
                ->send(new BookingReminderMail($this->booking));

            $this->booking->update(['reminder_sent_at' => now()]);

            Log::info('Booking reminder email sent', [
                'booking_id' => $this->booking->id,
                'customer_email' => $this->booking->customer->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking reminder email', [
                'booking_id' => $this->booking->id,
                'customer_email' => $this->booking->customer->email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
