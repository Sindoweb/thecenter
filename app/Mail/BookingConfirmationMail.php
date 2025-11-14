<?php

namespace App\Mail;

use App\Models\Booking;
use App\Services\CalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        $spaceNames = $this->booking->spaces->pluck('name')->implode(', ');

        return new Envelope(
            subject: "Booking Confirmed - {$spaceNames} at The Center",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-confirmation',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $calendarService = app(CalendarService::class);
        $icsContent = $calendarService->generateIcs($this->booking);

        return [
            Attachment::fromData(fn () => $icsContent, "booking-{$this->booking->id}.ics")
                ->withMime('text/calendar'),
        ];
    }
}
