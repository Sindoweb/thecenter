<?php

namespace App\Services;

use App\Models\Booking;

class CalendarService
{
    public function generateIcs(Booking $booking): string
    {
        $spaceNames = $booking->spaces->pluck('name')->implode(', ');
        $startDate = $booking->start_date->format('Ymd\THis\Z');
        $endDate = $booking->end_date->format('Ymd\THis\Z');
        $now = now()->format('Ymd\THis\Z');
        $uid = "booking-{$booking->id}@thecenter.test";

        $location = config('app.center_address', 'The Center');
        $organizerEmail = config('mail.from.address', 'hello@example.com');
        $organizerName = config('mail.from.name', 'The Center');

        $description = 'Booking at The Center\\n\\n';
        $description .= "Spaces: {$spaceNames}\\n";
        $description .= "Guests: {$booking->number_of_people}\\n";

        if ($booking->special_requests) {
            $description .= "Special Requests: {$booking->special_requests}\\n";
        }

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//The Center//Booking System//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:REQUEST\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= "DTSTAMP:{$now}\r\n";
        $ics .= "DTSTART:{$startDate}\r\n";
        $ics .= "DTEND:{$endDate}\r\n";
        $ics .= "SUMMARY:Booking at The Center - {$spaceNames}\r\n";
        $ics .= "DESCRIPTION:{$description}\r\n";
        $ics .= "LOCATION:{$location}\r\n";
        $ics .= "ORGANIZER;CN={$organizerName}:mailto:{$organizerEmail}\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "SEQUENCE:0\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }
}
