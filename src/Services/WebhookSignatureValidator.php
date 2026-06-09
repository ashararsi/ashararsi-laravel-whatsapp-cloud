<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Http\Request;

class WebhookSignatureValidator
{
    public function isValid(Request $request): bool
    {
        $secret = config('whatsapp.webhook.app_secret');

        if (empty($secret)) {
            return ! config('whatsapp.webhook.require_signature', false);
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (! is_string($signature) || ! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
