<?php

namespace App\Jobs;

use App\Mail\AdminNewBookingMail;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAdminNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function handle(): void
    {
        $adminEmail = config('mail.admin_email');

        if (! $adminEmail) {
            Log::warning('Admin email not configured, skipping admin notification', [
                'booking_id' => $this->booking->id,
            ]);

            return;
        }

        try {
            Mail::to($adminEmail)
                ->send(new AdminNewBookingMail($this->booking));

            Log::info('Admin notification email sent', [
                'booking_id' => $this->booking->id,
                'admin_email' => $adminEmail,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send admin notification email', [
                'booking_id' => $this->booking->id,
                'admin_email' => $adminEmail,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
