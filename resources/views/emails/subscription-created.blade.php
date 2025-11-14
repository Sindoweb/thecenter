@component('mail::message')
# Subscription Activated!

Hello {{ $subscription->customer->first_name }},

Your subscription at The Center has been successfully activated. Thank you for your commitment!

@component('mail::panel')
**Subscription Type:** {{ $subscription->booking_type->value }}

**Duration:** {{ $subscription->duration_type->value }}

**Price:** €{{ number_format($subscription->price, 2) }}

**Starts:** {{ $subscription->starts_at->format('F j, Y') }}

**Ends:** {{ $subscription->ends_at->format('F j, Y') }}

@if($subscription->usage_limit)
**Usage Limit:** {{ $subscription->usage_limit }} bookings
@else
**Usage Limit:** Unlimited
@endif
@endcomponent

## What's Next?

Your subscription is now active and ready to use. You can start making bookings immediately using your subscription benefits.

@component('mail::button', ['url' => config('app.url') . '/subscriptions'])
Manage Subscription
@endcomponent

## Important Notes

- Your subscription will automatically renew on {{ $subscription->ends_at->format('F j, Y') }}
@if($subscription->usage_limit)
- You have {{ $subscription->usage_limit }} available bookings during this period
@endif
- You can cancel your subscription at any time from your account dashboard

If you have any questions about your subscription, please contact us.

Best regards,<br>
The Center Team

---

**Contact Information:**<br>
Email: {{ config('mail.from.address') }}<br>
@if(config('app.center_phone'))
Phone: {{ config('app.center_phone') }}
@endif
@endcomponent
