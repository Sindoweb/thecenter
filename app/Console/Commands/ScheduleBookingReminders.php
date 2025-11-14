<?php

namespace App\Console\Commands;

use App\BookingStatus;
use App\Jobs\SendBookingReminderJob;
use App\Models\Booking;
use Illuminate\Console\Command;

class ScheduleBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Send reminder emails for bookings starting in 24-48 hours';

    public function handle(): int
    {
        $this->info('Searching for bookings that need reminders...');

        $startWindow = now()->addHours(24);
        $endWindow = now()->addHours(48);

        $bookings = Booking::query()
            ->with(['customer', 'spaces'])
            ->where('status', BookingStatus::Confirmed)
            ->whereBetween('start_date', [$startWindow, $endWindow])
            ->whereNull('reminder_sent_at')
            ->get();

        if ($bookings->isEmpty()) {
            $this->comment('No bookings found that need reminders.');

            return self::SUCCESS;
        }

        $this->info("Found {$bookings->count()} booking(s) that need reminders.");

        foreach ($bookings as $booking) {
            $this->comment("Dispatching reminder for booking #{$booking->id}...");

            SendBookingReminderJob::dispatch($booking);
        }

        $this->info("Successfully dispatched {$bookings->count()} reminder(s) to the queue.");

        return self::SUCCESS;
    }
}
