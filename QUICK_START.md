# Quick Start Guide - The Center Booking System

## 🚀 Get Started in 5 Minutes

### Step 1: Configure Environment (2 minutes)

```bash
# Copy environment file
cp .env.example .env

# Generate app key (if not done)
php artisan key:generate
```

**Update these in `.env`:**

```env
# Your Mollie API Key (get from https://www.mollie.com/dashboard)
MOLLIE_KEY=test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Your admin email
MAIL_ADMIN_EMAIL="your-email@example.com"

# Your center details
APP_CENTER_ADDRESS="123 Main Street, Amsterdam, Netherlands"
APP_CENTER_PHONE="+31 20 123 4567"
```

---

### Step 2: Database Setup (1 minute)

The database is already migrated and seeded! You have:
- ✅ 7 spaces (Glow, Ray, Universe, Sun, Moon, Co-Working, Light Center)
- ✅ 25 pricing rules (all prices configured)

**Check database:**

```bash
php artisan tinker
>>> \App\Models\Space::count()
=> 7
>>> \App\Models\PricingRule::count()
=> 25
```

---

### Step 3: Create Admin User (1 minute)

```bash
php artisan make:filament-user
```

Enter your details when prompted.

---

### Step 4: Start Services (1 minute)

**Terminal 1 - Queue Worker:**
```bash
php artisan queue:work
```

**Terminal 2 - Development Server (if needed):**
Laravel Herd handles this automatically! Your site is available at:
```
https://thecenter.test
```

---

### Step 5: Access Admin Panel

🎉 **You're ready!**

Visit: **https://thecenter.test/admin**

Login with the admin credentials you just created.

---

## 📋 What You Can Do Now

### In the Admin Panel:

1. **View/Edit Spaces** → `/admin/spaces`
   - See all 7 spaces
   - Edit capacities, features, combinations
   - Toggle active/inactive

2. **View/Edit Pricing** → `/admin/pricing-rules`
   - See all 25 pricing rules
   - Adjust prices by season
   - Add discounts

3. **Manage Customers** → `/admin/customers`
   - Add new customers
   - View booking history
   - Manage subscriptions

4. **Create Bookings** → `/admin/bookings/create`
   - Select customer
   - Choose spaces and dates
   - System validates conflicts automatically
   - Generate payment link

---

## 🧪 Test the System

### Test Conflict Detection

```bash
php artisan test --filter=ConflictDetectionTest
```

This runs 23 tests that verify:
- ✅ Universe blocks Glow + Ray
- ✅ Booking Glow blocks Universe
- ✅ Date overlap detection
- ✅ Co-working overflow capacity

### Test Payment System

```bash
php artisan test --filter=PaymentServiceTest
```

This runs 12 tests for payment processing.

### Run All Tests

```bash
php artisan test
```

161 tests covering all functionality.

---

## 🎯 Common Tasks

### Create a Test Booking

1. Go to `/admin/customers/create`
2. Add a test customer
3. Go to `/admin/bookings/create`
4. Select customer
5. Choose "The Glow" space
6. Pick tomorrow's date, 9 AM - 5 PM
7. Set 4 people
8. Click "Create"
9. System calculates €380 (full day)

### Test Email Notifications

```bash
# Check mail log (if using log driver)
tail -f storage/logs/laravel.log | grep "email"

# Or check queue jobs
php artisan queue:monitor

# Manually send reminder
php artisan bookings:send-reminders
```

### Test Conflict Detection

**Try this in the admin panel:**

1. Create booking for "The Universe" (tomorrow, 9 AM - 5 PM)
2. Try to create another booking for "The Glow" (same date/time)
3. ❌ System prevents it - shows error about conflicts
4. ✅ Conflict detection working!

---

## 💡 Pro Tips

### Quick Data Check

```bash
php artisan tinker

# See all spaces
>>> \App\Models\Space::pluck('name', 'id')

# Check a specific space
>>> \App\Models\Space::where('slug', 'the-glow')->first()

# See pricing for The Glow
>>> \App\Models\Space::where('slug', 'the-glow')->first()->pricingRules
```

### Clear Everything

```bash
# Clear caches
php artisan optimize:clear

# Restart queue
php artisan queue:restart

# Fresh database with seed data
php artisan migrate:fresh --seed
```

### Format Code

```bash
# Format all files
./vendor/bin/pint

# Format specific file
./vendor/bin/pint app/Services/PaymentService.php
```

---

## 🔧 Configuration

### Mollie Setup

1. Create account: https://www.mollie.com
2. Get API keys from dashboard
3. Add to `.env`: `MOLLIE_KEY=test_...`
4. Configure webhooks in Mollie dashboard:
   - Payment: `https://thecenter.test/webhook/mollie/payment`
   - Subscription: `https://thecenter.test/webhook/mollie/subscription`

### Email Setup (Production)

Update `.env` to use real SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS="noreply@thecenter.com"
MAIL_FROM_NAME="The Center"
```

---

## 📊 Pre-Configured Pricing

Everything is already set up! Here's what's in the database:

### Conference Rooms
- **The Glow (6 people):** €200 half-day | €380 full-day
- **The Ray (10 people):** €275 half-day | €500 full-day
- **The Universe (16 people):** €400 half-day | €700 full-day

### B&B Accommodation
- **Single room:** €110/night
- **Both rooms:** €200/night
- **Private rental:** €320/night

### Co-Working
- **Day pass:** €35
- **1 day/week:** €120/month
- **3 days/week:** €300/month
- **Unlimited:** €450/month

### Light Therapy
- **Session (2h):** €120
- **Night arrangement:** €440
- **4 sessions/month:** €400/month

---

## ❓ Troubleshooting

### Can't access admin panel?

```bash
# Create admin user
php artisan make:filament-user
```

### Emails not sending?

```bash
# Check queue is running
ps aux | grep "queue:work"

# Start queue
php artisan queue:work
```

### Database errors?

```bash
# Reset database
php artisan migrate:fresh --seed
```

### Conflict detection not working?

```bash
# Run validation tests
php artisan test --filter=BookingValidationServiceTest
```

---

## 📚 Next Steps

1. ✅ **Read:** `PROJECT_SUMMARY.md` for complete documentation
2. ✅ **Test:** Create sample bookings in admin panel
3. ✅ **Configure:** Set up Mollie payment testing
4. ✅ **Customize:** Adjust pricing and space details
5. ✅ **Deploy:** When ready, follow production checklist

---

## 🎉 You're All Set!

The system is fully functional with:
- ✅ 7 spaces configured
- ✅ 25 pricing rules active
- ✅ Payment system integrated
- ✅ Conflict detection working
- ✅ Email notifications ready
- ✅ 161 tests passing
- ✅ Admin panel accessible

**Start creating bookings at:** https://thecenter.test/admin/bookings/create

---

**Questions?** Check `PROJECT_SUMMARY.md` for detailed documentation.
