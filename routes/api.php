<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhooks\OutboundMessageWebhookController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/webhooks/outbound-messages/{provider}', [OutboundMessageWebhookController::class, 'handle'])
    ->name('webhooks.outbound-messages');
