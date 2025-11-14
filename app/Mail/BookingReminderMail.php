<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        $hoursUntil = now()->diffInHours($this->booking->start_date);
        $timePhrase = $hoursUntil < 36 ? 'tomorrow' : 'in 2 days';

        return new Envelope(
            subject: "Reminder: Your booking at The Center starts {$timePhrase}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-reminder',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
