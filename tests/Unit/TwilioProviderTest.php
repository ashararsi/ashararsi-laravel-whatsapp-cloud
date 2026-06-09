<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Providers\TwilioProvider;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class TwilioProviderTest extends TestCase
{
    #[Test]
    public function it_sends_text_via_twilio_api(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response([
                'sid' => 'SM1234567890',
                'status' => 'queued',
            ], 201),
        ]);

        $account = WhatsAppAccount::query()->create([
            'name' => 'twilio',
            'provider' => WhatsAppAccount::PROVIDER_TWILIO,
            'phone_number' => '923001234567',
            'twilio_sid' => 'AC123',
            'twilio_token' => 'twilio-token-1234567890',
            'twilio_whatsapp_number' => '14155238886',
            'is_default' => true,
            'is_active' => true,
        ]);

        $provider = new TwilioProvider($account);
        $result = $provider->sendText('923009999999', 'Hello Twilio');

        $this->assertSame('SM1234567890', $result->response['sid']);
        $this->assertSame('whatsapp:+14155238886', $result->payload['twilio']['From']);
        $this->assertSame('whatsapp:+923009999999', $result->payload['twilio']['To']);
    }

    #[Test]
    public function it_builds_twilio_payload_for_queue(): void
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'twilio-build',
            'provider' => WhatsAppAccount::PROVIDER_TWILIO,
            'phone_number' => '923001234567',
            'twilio_sid' => 'AC123',
            'twilio_token' => 'twilio-token-1234567890',
            'twilio_whatsapp_number' => '14155238886',
            'is_default' => true,
            'is_active' => true,
        ]);

        $provider = new TwilioProvider($account);
        $payload = $provider->buildPayload('text', '923009999999', ['text' => 'Queued']);

        $this->assertArrayHasKey('twilio', $payload);
        $this->assertSame('Queued', $payload['twilio']['Body']);
    }
}
