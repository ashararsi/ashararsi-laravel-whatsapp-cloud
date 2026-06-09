<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class V12WebhookSecurityTest extends TestCase
{
    #[Test]
    public function it_validates_meta_webhook_using_per_account_app_secret(): void
    {
        config([
            'whatsapp.webhook.app_secret' => 'global-secret',
            'whatsapp.webhook.require_signature' => true,
        ]);

        WhatsAppAccount::query()->create([
            'name' => 'secure',
            'phone_number' => '923001234567',
            'phone_number_id' => 'secure-phone-id',
            'provider' => 'meta',
            'access_token' => 'token-1234567890',
            'app_secret' => 'account-secret',
            'is_default' => true,
            'is_active' => true,
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => 'secure-phone-id'],
                        'messages' => [],
                    ],
                ]],
            ]],
        ];

        $body = json_encode($payload);
        $validSignature = 'sha256='.hash_hmac('sha256', $body, 'account-secret');
        $invalidSignature = 'sha256='.hash_hmac('sha256', $body, 'global-secret');

        $this->call(
            'POST',
            '/whatsapp/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $invalidSignature,
            ],
            $body,
        )->assertForbidden();

        $this->call(
            'POST',
            '/whatsapp/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $validSignature,
            ],
            $body,
        )->assertOk();
    }

    #[Test]
    public function it_falls_back_to_global_app_secret_when_account_secret_missing(): void
    {
        config([
            'whatsapp.webhook.app_secret' => 'global-secret',
            'whatsapp.webhook.require_signature' => true,
        ]);

        WhatsAppAccount::query()->create([
            'name' => 'fallback',
            'phone_number' => '923001234567',
            'phone_number_id' => 'fallback-phone-id',
            'provider' => 'meta',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => 'fallback-phone-id'],
                        'messages' => [],
                    ],
                ]],
            ]],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'global-secret');

        $this->call(
            'POST',
            '/whatsapp/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $body,
        )->assertOk();
    }
}
