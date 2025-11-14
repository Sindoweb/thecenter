@component('mail::message')
# New Booking Received

A new booking has been created in the system.

@component('mail::panel')
**Booking Reference:** #{{ $booking->id }}

**Status:** {{ $booking->status->value }}

**Payment Status:** {{ $booking->payment_status->value }}
@endcomponent

## Customer Information

**Name:** {{ $booking->customer->first_name }} {{ $booking->customer->last_name }}

**Email:** {{ $booking->customer->email }}

**Phone:** {{ $booking->customer->phone ?? 'Not provided' }}

## Booking Details

**Spaces:** {{ $booking->spaces->pluck('name')->implode(', ') }}

**Date:** {{ $booking->start_date->format('l, F j, Y') }}

**Time:** {{ $booking->start_date->format('g:i A') }} - {{ $booking->end_date->format('g:i A') }}

**Number of Guests:** {{ $booking->number_of_people }}

**Total Price:** €{{ number_format($booking->total_price, 2) }}

@if($booking->discount_amount > 0)
**Discount:** -€{{ number_format($booking->discount_amount, 2) }}
@endif

**Final Price:** €{{ number_format($booking->final_price, 2) }}

@if($booking->notes)
## Special Requests
{{ $booking->notes }}
@endif

@component('mail::button', ['url' => config('app.url') . '/admin/bookings/' . $booking->id])
View in Admin Panel
@endcomponent

---

This is an automated notification from The Center booking system.
@endcomponent
