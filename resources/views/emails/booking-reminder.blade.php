@component('mail::message')
# Booking Reminder

Hello {{ $booking->customer->first_name }},

This is a friendly reminder that your booking at The Center is coming up soon!

@component('mail::panel')
**Booking Reference:** #{{ $booking->id }}

**Spaces:** {{ $booking->spaces->pluck('name')->implode(', ') }}

**Date:** {{ $booking->start_date->format('l, F j, Y') }}

**Time:** {{ $booking->start_date->format('g:i A') }} - {{ $booking->end_date->format('g:i A') }}

**Number of Guests:** {{ $booking->number_of_people }}
@endcomponent

@if($booking->notes)
## Your Special Requests
{{ $booking->notes }}
@endcomponent

## Getting Here

@if(config('app.center_address'))
**Address:** {{ config('app.center_address') }}
@endif

@if(config('app.center_parking_info'))
**Parking:** {{ config('app.center_parking_info') }}
@endif

@component('mail::button', ['url' => config('app.url') . '/bookings/' . $booking->id])
View Booking Details
@endcomponent

If you need to make any changes or have questions, please contact us as soon as possible.

We look forward to seeing you!

Best regards,<br>
The Center Team

---

**Contact Information:**<br>
Email: {{ config('mail.from.address') }}<br>
@if(config('app.center_phone'))
Phone: {{ config('app.center_phone') }}
@endif
@endcomponent
