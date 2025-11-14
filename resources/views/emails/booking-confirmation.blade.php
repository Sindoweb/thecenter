@component('mail::message')
# Booking Confirmed!

Hello {{ $booking->customer->first_name }},

Your booking at The Center has been confirmed! We're looking forward to welcoming you.

@component('mail::panel')
**Booking Reference:** #{{ $booking->id }}

**Spaces:** {{ $booking->spaces->pluck('name')->implode(', ') }}

**Date:** {{ $booking->start_date->format('l, F j, Y') }}

**Time:** {{ $booking->start_date->format('g:i A') }} - {{ $booking->end_date->format('g:i A') }}

**Number of Guests:** {{ $booking->number_of_people }}

**Total Amount:** €{{ number_format($booking->final_price, 2) }}
@endcomponent

@if($booking->notes)
## Special Requests
{{ $booking->notes }}
@endif

A calendar invitation has been attached to this email for your convenience.

@component('mail::button', ['url' => config('app.url') . '/bookings/' . $booking->id])
View Booking Details
@endcomponent

If you have any questions or need to make changes, please don't hesitate to contact us.

Best regards,<br>
The Center Team

---

**Contact Information:**<br>
Email: {{ config('mail.from.address') }}<br>
@if(config('app.center_phone'))
Phone: {{ config('app.center_phone') }}<br>
@endif
@if(config('app.center_address'))
Address: {{ config('app.center_address') }}
@endif
@endcomponent
