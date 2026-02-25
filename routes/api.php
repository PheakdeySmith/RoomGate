<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhooks\BakongPaymentWebhookController;
use App\Http\Controllers\Webhooks\OutboundMessageWebhookController;
use App\Http\Controllers\Webhooks\PayPalPaymentWebhookController;
use App\Http\Controllers\Webhooks\StripePaymentWebhookController;

Route::middleware(['auth:sanctum', 'api.access'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/webhooks/outbound-messages/{provider}', [OutboundMessageWebhookController::class, 'handle'])
    ->name('webhooks.outbound-messages');

Route::post('/webhooks/payments/bakong', [BakongPaymentWebhookController::class, 'handle'])
    ->name('webhooks.payments.bakong');

Route::post('/webhooks/payments/stripe', [StripePaymentWebhookController::class, 'handle'])
    ->name('webhooks.payments.stripe');

Route::post('/webhooks/payments/paypal', [PayPalPaymentWebhookController::class, 'handle'])
    ->name('webhooks.payments.paypal');
