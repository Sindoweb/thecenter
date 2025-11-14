<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MollieWebhookController;
use Illuminate\Support\Facades\Route;

// Homepage with optional locale
Route::get('/{locale?}', [HomeController::class, 'index'])
    ->where('locale', 'en|nl')
    ->name('home');

// Booking routes
Route::prefix('{locale?}')->where(['locale' => 'en|nl'])->group(function () {
    Route::get('/booking', [BookingController::class, 'create'])->name('booking.create');
    Route::post('/booking/check-availability', [BookingController::class, 'checkAvailability'])->name('booking.check-availability');
    Route::post('/booking/get-pricing', [BookingController::class, 'getPricing'])->name('booking.get-pricing');
});

// Mollie webhook routes (excluded from CSRF protection)
Route::post('/webhook/mollie/payment', [MollieWebhookController::class, 'handlePaymentWebhook'])
    ->name('mollie.webhook.payment');

Route::post('/webhook/mollie/subscription', [MollieWebhookController::class, 'handleSubscriptionWebhook'])
    ->name('mollie.webhook.subscription');
