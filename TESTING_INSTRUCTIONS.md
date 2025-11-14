# Testing Instructions for The Center Booking System

## Quick Start

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Models/BookingTest.php

# Run specific test by name
php artisan test --filter="can create a booking"

# Run tests with coverage (requires Xdebug)
php artisan test --coverage
```

## Fixing Failing Tests

Most tests are failing due to a single issue: the `booking_spaces` pivot table requires `duration_type` and `price` fields.

### Option 1: Create a Test Helper (Recommended)

Add this to `tests/Pest.php`:

```php
use App\Models\Booking;
use App\Models\Space;

function attachSpace(
    Booking $booking,
    Space $space,
    string $durationType = 'full_day',
    float $price = 500.00
): void {
    $booking->spaces()->attach($space->id, [
        'duration_type' => $durationType,
        'price' => $price,
    ]);
}
```

Then in tests, replace:
```php
$booking->spaces()->attach($space->id);
```

With:
```php
attachSpace($booking, $space);
```

### Option 2: Global Search & Replace

Run these commands to fix all test files:

```bash
# Backup first!
cp -r tests tests_backup

# Fix all attach() calls (this is a simplified approach)
# You may need to do this manually for each test file
```

**Manual fix example in `BookingTest.php`:**

Change from:
```php
$booking->spaces()->attach($space->id);
```

To:
```php
$booking->spaces()->attach($space->id, [
    'duration_type' => 'full_day',
    'price' => 500.00,
]);
```

## Test Execution Order (Recommended)

Run tests in this order to verify the system:

### 1. Model Tests
```bash
# Start with simple model tests
php artisan test tests/Feature/Models/CustomerTest.php
php artisan test tests/Feature/Models/SpaceTest.php
php artisan test tests/Feature/Models/BookingTest.php
php artisan test tests/Feature/Models/SubscriptionTest.php
```

### 2. Service Tests
```bash
# Core business logic - most important!
php artisan test tests/Unit/Services/BookingValidationServiceTest.php
php artisan test tests/Unit/Services/CalendarServiceTest.php
php artisan test tests/Unit/Services/PaymentServiceTest.php
php artisan test tests/Unit/Services/SubscriptionServiceTest.php
```

### 3. Integration Tests
```bash
# Complex conflict scenarios
php artisan test tests/Feature/ConflictDetectionTest.php
```

## Key Test Scenarios

### Testing Combined Space Logic
The most critical business logic:

```bash
# Test that Universe blocks Glow and Ray
php artisan test --filter="Universe booking blocks"

# Test that Glow/Ray block Universe
php artisan test --filter="Glow booking blocks Universe"
php artisan test --filter="Ray booking blocks Universe"
```

### Testing Co-working Overflow
```bash
php artisan test --filter="getCoWorkingCapacity"
```

### Testing Booking Lifecycle
```bash
php artisan test --filter="confirm a booking"
php artisan test --filter="cancel a booking"
```

## Test Data Setup

All tests use the `SpacesAndPricingSeeder` which creates:
- **The Glow** (Conference Room, 6 capacity)
- **The Ray** (Conference Room, 10 capacity)
- **The Universe** (Combined space using Glow + Ray, 16 capacity)
- **The Sun** (Accommodation, 2 capacity)
- **The Moon** (Accommodation, 2 capacity)
- **Co-Working Area** (6 capacity, can overflow to conference rooms)
- **The Light Center** (Therapy Room, 2 capacity)

## Common Issues & Solutions

### Issue: "table bookings has no column named notes"
**Solution**: Already fixed. The model now uses `special_requests` and `internal_notes`.

### Issue: "table customers has no column named first_name"
**Solution**: Already fixed. The model now uses single `name` field and `company`.

### Issue: "NOT NULL constraint failed: booking_spaces.duration_type"
**Solution**: This is the main issue. Follow "Fixing Failing Tests" section above.

### Issue: "Class 'SubscriptionUsage' not found"
**Solution**: Create the model:
```bash
php artisan make:model SubscriptionUsage
```

Add to the model:
```php
protected $fillable = [
    'subscription_id',
    'booking_id',
    'used_at',
];

protected function casts(): array
{
    return [
        'used_at' => 'datetime',
    ];
}
```

Then create the factory:
```bash
php artisan make:factory SubscriptionUsageFactory
```

### Issue: Mollie/Cashier tests failing
**Solution**: Tests requiring Mollie are marked with `->skip()`. To enable:

1. Install Mockery if not present:
```bash
composer require mockery/mockery --dev
```

2. Update tests to use mocks:
```php
use Mockery;

it('creates payment with Mollie', function () {
    $mockPayment = Mockery::mock();
    $mockPayment->id = 'tr_test123';
    $mockPayment->shouldReceive('getCheckoutUrl')
        ->andReturn('https://checkout.mollie.com/test');

    // ... rest of test
});
```

## Continuous Integration

Add to your CI pipeline (e.g., GitHub Actions):

```yaml
- name: Run Pest Tests
  run: |
    php artisan test --parallel
```

## Test Coverage

To generate coverage report (requires Xdebug):

```bash
# HTML coverage report
php artisan test --coverage --coverage-html coverage

# Open in browser
open coverage/index.html
```

## Performance Testing

```bash
# Run with profiling
php artisan test --profile

# Run specific slow tests
php artisan test --filter="Universe" --profile
```

## Debugging Tests

### Enable verbose output
```bash
php artisan test -vvv
```

### Stop on failure
```bash
php artisan test --stop-on-failure
```

### Run single test with full output
```bash
php artisan test --filter="can create a booking" -vvv
```

### Debug with Ray (if installed)
In any test:
```php
ray($booking)->green();
ray($booking->spaces)->purple();
```

## Best Practices for Adding New Tests

1. **Use factories** - Don't create models manually
2. **Use states** - `Booking::factory()->confirmed()->create()`
3. **Use descriptive names** - `it('prevents booking when Universe is already booked')`
4. **Group related tests** - Use `describe()` blocks
5. **Test edge cases** - Don't just test happy paths
6. **Keep tests isolated** - Use `RefreshDatabase` trait
7. **Mock external services** - Don't hit real APIs

## Example: Adding a New Test

```php
// tests/Feature/Models/BookingTest.php

it('prevents overbooking when capacity is exceeded', function () {
    $space = Space::factory()->create(['capacity' => 5]);

    // Create booking with 5 people (at capacity)
    $booking1 = Booking::factory()->confirmed()->create([
        'start_date' => now()->addDays(10),
        'end_date' => now()->addDays(12),
        'number_of_people' => 5,
    ]);
    attachSpace($booking1, $space);

    // Try to create overlapping booking
    $service = app(BookingValidationService::class);
    $errors = $service->validateBooking(
        collect([$space]),
        now()->addDays(10),
        now()->addDays(12),
        1 // Even 1 more person should fail
    );

    expect($errors)->not->toBeEmpty()
        ->and($errors[0])->toContain('not available');
});
```

## Resources

- [Pest Documentation](https://pestphp.com)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [Pest Laravel Plugin](https://pestphp.com/docs/plugins/laravel)
- [TEST_SUITE_SUMMARY.md](/Users/sindo/Sites/thecenter/TEST_SUITE_SUMMARY.md) - Detailed test coverage overview

## Getting Help

If tests are failing:

1. Check the error message carefully
2. Verify database schema matches model expectations
3. Ensure seeders have run (`php artisan db:seed`)
4. Check factory definitions match database columns
5. Review this document for common issues

## Quick Wins - Tests to Run First

These tests should pass immediately after fixing the pivot table issue:

```bash
# Core model functionality
php artisan test --filter="can create a customer"
php artisan test --filter="can create a space"
php artisan test --filter="getDuration"
php artisan test --filter="isActive"

# Basic scopes
php artisan test --filter="active scope"
php artisan test --filter="ofType scope"

# Factory states
php artisan test --filter="factory states"
```

Good luck with your testing! 🚀
