@component('mail::message')
# Booking Cancelled

Hello {{ $booking->customer->first_name }},

Your booking at The Center has been cancelled.

@component('mail::panel')
**Booking Reference:** #{{ $booking->id }}

**Spaces:** {{ $booking->spaces->pluck('name')->implode(', ') }}

**Original Date:** {{ $booking->start_date->format('l, F j, Y') }}

**Original Time:** {{ $booking->start_date->format('g:i A') }} - {{ $booking->end_date->format('g:i A') }}

**Cancelled On:** {{ $booking->cancelled_at->format('l, F j, Y g:i A') }}
@endcomponent

@if($cancellationReason)
## Cancellation Reason
{{ $cancellationReason }}
@endif

@if($booking->payment_status->value === 'paid')
## Refund Information
Your payment of €{{ number_format($booking->final_price, 2) }} will be refunded according to our cancellation policy. Please allow 5-10 business days for the refund to appear in your account.
@endif

If you have any questions about this cancellation or would like to make a new booking, please don't hesitate to contact us.

@component('mail::button', ['url' => config('app.url') . '/bookings'])
Make New Booking
@endcomponent

Best regards,<br>
The Center Team

---

**Contact Information:**<br>
Email: {{ config('mail.from.address') }}<br>
@if(config('app.center_phone'))
Phone: {{ config('app.center_phone') }}
@endif
@endcomponent
