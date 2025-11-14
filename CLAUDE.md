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
- Space combinations are stored in the `can_combine_with` JSON column on the spaces table
- All conflict checking handled in `BookingValidationService::getConflictingSpaceIds()`

#### 2. Conflict Detection

Complex overlap detection in `BookingValidationService`:
- Checks direct space conflicts
- Checks combined space conflicts (e.g., Universe blocks Glow + Ray)
- Checks component space conflicts (Glow blocks Universe)
- Uses database-level date range overlap: `start_date < $endDate AND end_date > $startDate`

#### 3. Co-Working Overflow Capacity

Co-working has dynamic capacity calculated in `BookingValidationService::getCoWorkingCapacity()`:
- Base: 6 people in dedicated co-working area
- When "The Glow" is available: +8 capacity
- When "The Ray" is available: +12 capacity
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

**Conference Room Quarterly Subscriptions:**
- Customer reserves 4 bookings (half or full days) per quarter at a fixed price
- 20% discount applied to standard rates
- `usage_limit: 4` per quarter
- Each booking increments `usage_count`
- Can mix half-day and full-day bookings within the 4-booking allowance
- Usage resets every quarter (3 months)

**Co-Working Subscription Tiers:**
- Subscriptions have a `usage_limit` field (e.g., 4 for 1 day/week, 12 for 3 days/week)
- Unlimited subscriptions have `usage_limit: null`
- Each day used increments `usage_count`
- When `usage_count >= usage_limit`, customer must upgrade or wait for renewal
- The `min_people`/`max_people` fields in pricing rules are used to distinguish subscription tiers (1, 3, 999 for unlimited)

**Light Therapy Subscription Tiers:**
- Session subscription: 4 sessions/month for €400 (`usage_limit: 4`, identified by `min_people: 1`)
- Overnight package subscription: 4 overnight sessions/month for €1,600 (`usage_limit: 4`, identified by `min_people: 2`)
- Each session used increments `usage_count`
- Usage resets monthly

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

**Important**: Enums are located in the root `app/` namespace (not `app/Enums/`). All enums use snake_case string values and some use Dutch terms:

- `SpaceType`: `conference_room`, `accommodation`, `co_working`, `therapy_room`, `combined`
- `BookingType`: `conferentie` (Dutch!), `accommodation`, `co_working`, `light_therapy`, `package`
- `BookingStatus`: `pending`, `confirmed`, `cancelled`, `completed`, `no_show`
- `PaymentStatus`: `pending`, `paid`, `failed`, `refunded`, `partially_refunded`
- `DurationType`: `halve_dag`, `hele_dag`, `nacht`, `sessie`, `dagpas`, `wekelijks`, `maandelijks`, `kwartaal` (all Dutch!)

**Critical**: When working with database queries or validation, use the snake_case/Dutch string values, NOT the PascalCase enum names.

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

#### Model Observers

`BookingObserver` automatically sets `total_price` equal to `price` when creating/updating bookings if `total_price` is null. This ensures the total price is always initialized before discounts are applied.

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
- The Glow (8 people): €140 half-day, €250 full-day
- The Ray (12 people): €160 half-day, €300 full-day
- The Universe (20 people): €400 half-day, €700 full-day
- Quarterly subscriptions: Reserve 4 half or full days per quarter with 20% discount on standard rates

### B&B Accommodation
- Single room (The Sun or The Moon, up to 2 people): €110/night
- Both rooms (up to 4 people): €200/night
- Weekend package (Fri-Sun): €110/night base + €50 per person per 2-hour light therapy session

### Co-Working
- Day pass: €35 (one-time booking)
- Monthly subscription tiers:
  - 1 day/week: €120/month (usage_limit: 4 days/month)
  - 3 days/week: €300/month (usage_limit: 12 days/month)
  - Unlimited (mon-fri): €450/month (usage_limit: null)

### Light Therapy
- Private session (2 hours): €120
- Private session subscription: 4 sessions/month for €400
- Private overnight package (stay + session): €440
- Private overnight package subscription: 4 sessions/month for €1,600

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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.27
- filament/filament (FILAMENT) - v3
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/flux (FLUXUI_FREE) - v2
- livewire/flux-pro (FLUXUI_PRO) - v2
- livewire/livewire (LIVEWIRE) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== herd rules ===

## Laravel Herd

- The application is served by Laravel Herd and will be available at: https?://[kebab-case-project-dir].test. Use the `get-absolute-url` tool to generate URLs for the user to ensure valid URLs.
- You must not run any commands to make the site available via HTTP(s). It is _always_ available through Laravel Herd.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== fluxui-pro/core rules ===

## Flux UI Pro

- This project is using the Pro version of Flux UI. It has full access to the free components and variants, as well as full access to the Pro components and variants.
- Flux UI is a component library for Livewire. Flux is a robust, hand-crafted, UI component library for your Livewire applications. It's built using Tailwind CSS and provides a set of components that are easy to use and customize.
- You should use Flux UI components when available.
- Fallback to standard Blade components if Flux is unavailable.
- If available, use Laravel Boost's `search-docs` tool to get the exact documentation and code snippets available for this project.
- Flux UI components look like this:

<code-snippet name="Flux UI component usage example" lang="blade">
    <flux:button variant="primary"/>
</code-snippet>


### Available Components
This is correct as of Boost installation, but there may be additional components within the codebase.

<available-flux-components>
accordion, autocomplete, avatar, badge, brand, breadcrumbs, button, calendar, callout, card, chart, checkbox, command, context, date-picker, dropdown, editor, field, heading, file upload, icon, input, modal, navbar, pagination, pillbox, popover, profile, radio, select, separator, switch, table, tabs, text, textarea, toast, tooltip
</available-flux-components>


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== pest/v4 rules ===

## Pest 4

- Pest v4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest v4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.
<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== filament/filament rules ===

## Filament
- Filament is used by this application, check how and where to follow existing application conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- You can use the `search-docs` tool to get information from the official Filament documentation when needed. This is very useful for Artisan command arguments, specific code examples, testing functionality, relationship management, and ensuring you're following idiomatic practices.
- Utilize static `make()` methods for consistent component initialization.

### Artisan
- You must use the Filament specific Artisan commands to create new files or components for Filament. You can find these with the `list-artisan-commands` tool, or with `php artisan` and the `--help` option.
- Inspect the required options, always pass `--no-interaction`, and valid arguments for other options when applicable.

### Filament's Core Features
- Actions: Handle doing something within the application, often with a button or link. Actions encapsulate the UI, the interactive modal window, and the logic that should be executed when the modal window is submitted. They can be used anywhere in the UI and are commonly used to perform one-time actions like deleting a record, sending an email, or updating data in the database based on modal form input.
- Forms: Dynamic forms rendered within other features, such as resources, action modals, table filters, and more.
- Infolists: Read-only lists of data.
- Notifications: Flash notifications displayed to users within the application.
- Panels: The top-level container in Filament that can include all other features like pages, resources, forms, tables, notifications, actions, infolists, and widgets.
- Resources: Static classes that are used to build CRUD interfaces for Eloquent models. Typically live in `app/Filament/Resources`.
- Schemas: Represent components that define the structure and behavior of the UI, such as forms, tables, or lists.
- Tables: Interactive tables with filtering, sorting, pagination, and more.
- Widgets: Small component included within dashboards, often used for displaying data in charts, tables, or as a stat.

### Relationships
- Determine if you can use the `relationship()` method on form components when you need `options` for a select, checkbox, repeater, or when building a `Fieldset`:

<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required(),
</code-snippet>


## Testing
- It's important to test Filament functionality for user satisfaction.
- Ensure that you are authenticated to access the application within the test.
- Filament uses Livewire, so start assertions with `livewire()` or `Livewire::test()`.

### Example Tests

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($users->last()->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Howdy',
            'email' => 'howdy@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Howdy',
        'email' => 'howdy@example.com',
    ]);
</code-snippet>

<code-snippet name="Testing Multiple Panels (setup())" lang="php">
    use Filament\Facades\Filament;

    Filament::setCurrentPanel('app');
</code-snippet>

<code-snippet name="Calling an Action in a Test" lang="php">
    livewire(EditInvoice::class, [
        'invoice' => $invoice,
    ])->callAction('send');

    expect($invoice->refresh())->isSent()->toBeTrue();
</code-snippet>


## Version 3 Changes To Focus On
- Resources are located in `app/Filament/Resources/` directory.
- Resource pages (List, Create, Edit) are auto-generated within the resource's directory - e.g., `app/Filament/Resources/PostResource/Pages/`.
- Forms use the `Forms\Components` namespace for form fields.
- Tables use the `Tables\Columns` namespace for table columns.
- A new `Filament\Forms\Components\RichEditor` component is available.
- Form and table schemas now use fluent method chaining.
- Added `php artisan filament:optimize` command for production optimization.
- Requires implementing `FilamentUser` contract for production access control.
</laravel-boost-guidelines>
