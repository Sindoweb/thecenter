<?php

use App\Http\Controllers\MollieWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Mollie webhook routes (excluded from CSRF protection)
Route::post('/webhook/mollie/payment', [MollieWebhookController::class, 'handlePaymentWebhook'])
    ->name('mollie.webhook.payment');

Route::post('/webhook/mollie/subscription', [MollieWebhookController::class, 'handleSubscriptionWebhook'])
    ->name('mollie.webhook.subscription');
