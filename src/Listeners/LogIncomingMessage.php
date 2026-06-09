<?php

namespace Vendor\LaravelWhatsAppCloud\Listeners;

use Illuminate\Support\Facades\Log;
use Vendor\LaravelWhatsAppCloud\Events\MessageReceived;

class LogIncomingMessage
{
    public function handle(MessageReceived $event): void
    {
        Log::info('WhatsApp message received', [
            'account' => $event->account->name,
            'payload' => $event->payload,
        ]);
    }
}
