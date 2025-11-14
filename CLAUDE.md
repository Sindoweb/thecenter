# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is **The Center** - a Laravel 12 booking management system for a multi-purpose co-working space, conference rooms, B&B accommodation, and light therapy center. The system handles complex booking logic, payment processing via Mollie, subscription management, and automated notifications.

## Common Commands

### Development Environment

```bash
# Start all development services (Laravel Herd + queue + logs + Vite)
composer run dev

# Setup project from scratch
composer run setup

# Run tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ConflictDetectionTest.php

# Run tests with filter
php artisan test --filter=BookingValidationServiceTest

# Format code (always run before committing)
vendor/bin/pint --dirty
```

### Database

```bash
# Run migrations
php artisan migrate

# Seed spaces and pricing rules
php artisan db:seed --class=SpacesAndPricingSeeder

# Fresh migration with all seeds
php artisan migrate:fresh --seed

# Create Filament admin user
php artisan make:filament-user
```

### Queue & Scheduling

```bash
# Start queue worker (required for emails)
php artisan queue:work

# Manually send booking reminders (normally runs daily via scheduler)
php artisan bookings:send-reminders

# Monitor queue
php artisan queue:monitor
```

### Frontend

```bash
# Build assets for production
npm run build

# Run Vite dev server
npm run dev
```

## Architecture & Business Logic

### Core Domain Models

The system revolves around 8 primary models with complex relationships:

- **Space**: Multi-purpose spaces (conference rooms, B&B, co-working, therapy rooms, combined spaces)
- **Booking**: Central booking entity with conflict detection
- **Customer**: Mollie Billable customers
- **Subscription**: Recurring subscriptions with usage tracking
- **Payment**: One-time and recurring payment records
- **PricingRule**: Dynamic pricing based on space type, booking type, duration, and date ranges

### Critical Business Rules

#### 1. Combined Space Logic

"The Universe" is a combined space requiring both "The Glow" and "The Ray":
- Booking The Universe automatically blocks both component spaces
- Booking either component space blocks The Universe
- All handled in `BookingValidationService::getConflictingSpaceIds()`

#### 2. Conflict Detection

Complex overlap detection in `BookingValidationService`:
- Checks direct space conflicts
- Checks combined space conflicts (e.g., Universe blocks Glow + Ray)
- Checks component space conflicts (Glow blocks Universe)
- Uses database-level date range overlap: `start_date < $endDate AND end_date > $startDate`

#### 3. Co-Working Overflow Capacity

Co-working has dynamic capacity calculated in `BookingValidationService::getCoWorkingCapacity()`:
- Base: 6 people in dedicated co-working area
- When "The Glow" is available: +6 capacity
- When "The Ray" is available: +10 capacity
- Automatically calculated for availability checks

#### 4. Booking Status Flow

```
Pending → (payment confirmed) → Confirmed → Completed
             ↓
          Cancelled (with optional refund)
```

Managed via Booking model methods: `confirm()`, `cancel()`

#### 5. Subscription Management

Handled by `SubscriptionService`:
- Creates Mollie recurring subscriptions via Laravel Cashier
- Tracks usage count vs. usage limits
- Supports unlimited subscriptions (therapy, co-working)
- Automatically resets usage count on renewal

### Service Layer Architecture

#### BookingValidationService

The most complex service - handles all availability and conflict logic:
- `isSpaceAvailable()`: Check single space availability
- `areSpacesAvailable()`: Check multiple spaces
- `getConflictingBookings()`: Get all conflicting bookings including combined space logic
- `validateBooking()`: Complete validation with error messages
- `getCoWorkingCapacity()`: Calculate dynamic co-working capacity

**Critical**: Always check combined spaces when validating bookings.

#### PaymentService

Handles one-time Mollie payments:
- `createPayment()`: Generate Mollie payment for booking
- `handleWebhook()`: Process payment confirmation webhooks
- `refundPayment()`: Process full or partial refunds

#### SubscriptionService

Manages recurring subscriptions:
- `createSubscription()`: Create Mollie subscription via Cashier
- `recordUsage()`: Track subscription usage with limits
- `cancelSubscription()`: Cancel on both Mollie and locally
- `renewSubscription()`: Renew for next period and reset usage

#### CalendarService

Generates `.ics` calendar attachments for booking confirmation emails.

### Enums

All enums use PascalCase values (e.g., `ConferenceRoom`, `HalfDay`):
- `SpaceType`: ConferenceRoom, Accommodation, CoWorking, TherapyRoom, Combined
- `BookingType`: Conference, Accommodation, CoWorking, LightTherapy, Package
- `BookingStatus`: Pending, Confirmed, Cancelled, Completed, NoShow
- `PaymentStatus`: Pending, Paid, Failed, Refunded, PartiallyRefunded
- `DurationType`: HalfDay, FullDay, Night, Session, DayPass, Weekly, Monthly, Quarterly

### Filament Admin Resources

Located in `app/Filament/Resources/`:
- **BookingResource**: Main booking management with custom actions (Confirm, Cancel)
- **CustomerResource**: Customer CRUD with relation managers for bookings/subscriptions
- **SpaceResource**: Space management with combination rules
- **PricingRuleResource**: Dynamic pricing configuration

Access admin panel at: `https://thecenter.test/admin`

### Notifications & Jobs

Email notifications are queued for performance:
- **BookingConfirmationMail**: Sent after payment confirmation (includes .ics)
- **BookingReminderMail**: Sent 24-48 hours before booking
- **BookingCancelledMail**: Sent on cancellation
- **SubscriptionCreatedMail**: Sent on new subscription
- **AdminNewBookingMail**: Sent to admin for new bookings

Jobs:
- `SendBookingConfirmationJob`
- `SendBookingReminderJob`
- `SendAdminNotificationJob`

Console command: `php artisan bookings:send-reminders` (scheduled daily)

### Webhook Handling

Mollie webhooks (excluded from CSRF in `bootstrap/app.php`):
- `/webhook/mollie/payment`: Payment status updates
- `/webhook/mollie/subscription`: Subscription status updates

Handler: `MollieWebhookController`

**Important**: Configure these URLs in Mollie dashboard for production.

## Testing Strategy

### Test Suite (161 tests)

Tests are comprehensive and critical for this project:
- **Feature/Models/**: Model behavior and relationships (15-22 tests per model)
- **Feature/ConflictDetectionTest.php**: Complex conflict scenarios (23 tests)
- **Unit/Services/**: Service layer unit tests (44 tests for BookingValidationService)

### Critical Test Scenarios

The conflict detection tests are particularly important:
- Combined space blocking (Universe blocks Glow + Ray)
- Component space blocking (Glow blocks Universe)
- Co-working overflow capacity calculation
- Date range edge cases (same-day bookings, touching boundaries)
- Subscription usage limits

### Running Tests

Always run affected tests after changes:

```bash
# After changing conflict logic
php artisan test --filter=ConflictDetection

# After changing validation logic
php artisan test --filter=BookingValidation

# After changing model
php artisan test tests/Feature/Models/BookingTest.php
```

## Configuration

### Environment Variables

Key configuration in `.env`:

```env
# Mollie Payments (critical)
MOLLIE_KEY=test_xxxxxx  # or live_xxxxxx for production
CASHIER_CURRENCY=EUR
CASHIER_CURRENCY_LOCALE=nl_NL

# Email (required for notifications)
MAIL_FROM_ADDRESS="noreply@thecenter.test"
MAIL_ADMIN_EMAIL="admin@thecenter.test"

# Queue (use 'database' for simplicity)
QUEUE_CONNECTION=database

# The Center contact info (used in emails)
APP_CENTER_ADDRESS="The Center, Street Address, City, Country"
APP_CENTER_PHONE="+31 20 123 4567"
APP_CENTER_PARKING_INFO="Free parking available on-site"
```

### Database Schema

8 tables with the following relationships:
- `spaces`: All bookable spaces
- `pricing_rules`: Flexible pricing engine
- `customers`: Mollie Billable trait for payments
- `bookings`: Main booking records (has direct `space_id` column)
- `booking_spaces`: Legacy pivot table (deprecated, kept for compatibility)
- `subscriptions`: Mollie recurring subscriptions
- `subscription_usages`: Usage tracking
- `payments`: Payment transaction history

**Note**: Bookings now use direct `space_id` relationship, but `booking_spaces` pivot table still exists for backwards compatibility.

## Code Conventions

### Laravel 12 Specific

- No `app/Http/Middleware/` directory
- Middleware registered in `bootstrap/app.php`
- No `app/Console/Kernel.php` - commands auto-register from `app/Console/Commands/`
- Casts defined in `casts()` method, not `$casts` property

### Models

- Use constructor property promotion
- Use typed relationships with return types
- Use `casts()` method for Laravel 12
- Add scopes for common queries (e.g., `scopeUpcoming()`, `scopeByStatus()`)

### Services

- Business logic extracted to service classes
- All services return domain models, not arrays
- Use database transactions for multi-step operations
- Log important events with context

### Validation

- Form Requests for all validation (check existing for array vs string format)
- Validation happens in BookingValidationService for complex booking rules
- Always validate conflicts before creating bookings

## Pricing Configuration

All pricing is seeded via `SpacesAndPricingSeeder`. Modify through Filament admin panel.

### Conference Rooms
- The Glow (6 people): €200 half-day, €380 full-day
- The Ray (10 people): €275 half-day, €500 full-day
- The Universe (16 people): €400 half-day, €700 full-day
- Quarterly subscriptions: 10% discount

### B&B Accommodation
- Single room: €110/night
- Both rooms: €200/night
- Private rental: €320/night
- Weekend + therapy package: +€50/person

### Co-Working
- Day pass: €35
- 1 day/week: €120/month
- 3 days/week: €300/month
- Unlimited: €450/month

### Light Therapy
- Private session (2h): €120
- Night arrangement: €440
- 4 sessions/month: €400/month subscription

## Known Issues

### Deprecated Pivot Table

The `booking_spaces` pivot table still exists but bookings now use direct `space_id` relationship. The `spaces()` relationship on Booking model is marked deprecated. Use `space()` instead.

### Customer Name Field

Customer model has `name` field but some factories use `first_name`/`last_name`. This causes some test inconsistencies but doesn't affect functionality.

## Key Files to Check

When modifying booking logic, always review:
- `app/Services/BookingValidationService.php` - Conflict detection
- `tests/Feature/ConflictDetectionTest.php` - Conflict test scenarios
- `app/Models/Booking.php` - Booking model with relationships
- `app/Models/Space.php` - Space model with combination logic
- `database/seeders/SpacesAndPricingSeeder.php` - Initial space/pricing setup

## Development Workflow

1. **Always run Pint before committing**: `vendor/bin/pint --dirty`
2. **Run affected tests**: `php artisan test --filter=YourTest`
3. **Queue worker must run for emails**: `composer run dev` includes queue worker
4. **Use factories for test data**: All models have factories with useful states
5. **Check existing conventions**: Look at sibling files before creating new ones

## Production Checklist

Before deploying:
- Update `.env` with production Mollie API key
- Configure Mollie webhook URLs in dashboard
- Set up proper email service (not `log` driver)
- Configure queue worker as system service
- Set up cron for scheduler: `* * * * * cd /path && php artisan schedule:run`
- Run `php artisan optimize` for production optimization
- Configure SSL certificates
- Set up database backups
- Test payment flow end-to-end
- Test webhook handling with Mollie's test webhooks
