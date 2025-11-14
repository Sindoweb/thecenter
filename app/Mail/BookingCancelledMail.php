<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $cancellationReason
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Booking Cancelled - #{$this->booking->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-cancelled',
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
