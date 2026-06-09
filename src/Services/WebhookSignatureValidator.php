<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Http\Request;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

class WebhookSignatureValidator
{
    public function isValid(Request $request, ?WhatsAppAccount $account = null): bool
    {
        $secret = $this->resolveSecret($account);

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

    public function resolveSecret(?WhatsAppAccount $account): ?string
    {
        if ($account?->app_secret) {
            return (string) $account->app_secret;
        }

        $global = config('whatsapp.webhook.app_secret');

        return is_string($global) && $global !== '' ? $global : null;
    }
}
