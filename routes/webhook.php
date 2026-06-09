<?php

use Illuminate\Support\Facades\Route;
use Vendor\LaravelWhatsAppCloud\Http\Controllers\WebhookController;

$prefix = config('whatsapp.webhook.prefix', 'whatsapp');
$middleware = config('whatsapp.webhook.middleware', []);

Route::middleware($middleware)
    ->prefix($prefix)
    ->group(function () {
        Route::get('/webhook', [WebhookController::class, 'verify']);
        Route::post('/webhook', [WebhookController::class, 'handle']);
    });
