<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MollieWebhookController;
use Illuminate\Support\Facades\Route;

// Homepage with optional locale
Route::get('/{locale?}', [HomeController::class, 'index'])
    ->where('locale', 'en|nl')
    ->name('home');

// Mollie webhook routes (excluded from CSRF protection)
Route::post('/webhook/mollie/payment', [MollieWebhookController::class, 'handlePaymentWebhook'])
    ->name('mollie.webhook.payment');

Route::post('/webhook/mollie/subscription', [MollieWebhookController::class, 'handleSubscriptionWebhook'])
    ->name('mollie.webhook.subscription');
