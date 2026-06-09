<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
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
}
