<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Services\WebhookSignatureValidator;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class WebhookSignatureValidatorTest extends TestCase
{
    #[Test]
    public function it_validates_signature_when_secret_is_configured(): void
    {
        config(['whatsapp.webhook.app_secret' => 'secret']);

        $body = '{"entry":[]}';
        $signature = 'sha256='.hash_hmac('sha256', $body, 'secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body);

        $validator = new WebhookSignatureValidator;

        $this->assertTrue($validator->isValid($request));
    }

    #[Test]
    public function it_rejects_invalid_signature(): void
    {
        config(['whatsapp.webhook.app_secret' => 'secret']);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
        ], '{"entry":[]}');

        $this->assertFalse((new WebhookSignatureValidator)->isValid($request));
    }

    #[Test]
    public function it_prefers_per_account_app_secret_over_global_secret(): void
    {
        config(['whatsapp.webhook.app_secret' => 'global']);

        $account = new WhatsAppAccount(['app_secret' => 'per-account']);

        $body = '{"entry":[]}';
        $signature = 'sha256='.hash_hmac('sha256', $body, 'per-account');

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body);

        $validator = new WebhookSignatureValidator;

        $this->assertTrue($validator->isValid($request, $account));
        $this->assertSame('per-account', $validator->resolveSecret($account));
    }
}
