<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Tests\Mocks\MockWhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class WhatsAppManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(WhatsAppClientInterface::class, new MockWhatsAppClient);
    }

    protected function createAccount(string $name = 'default', bool $isDefault = true): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => $name,
            'provider' => WhatsAppAccount::PROVIDER_META,
            'phone_number' => '923001234567',
            'phone_number_id' => 'phone-id-'.$name,
            'access_token' => 'token-'.$name.'1234567890',
            'is_default' => $isDefault,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_sends_text_message_using_default_account(): void
    {
        $this->createAccount();

        $message = WhatsApp::send('923009999999', 'Hello World');

        $this->assertInstanceOf(WhatsAppMessage::class, $message);
        $this->assertSame('sent', $message->status);
        $this->assertSame('text', $message->type);
        $this->assertDatabaseHas('whatsapp_messages', [
            'to' => '923009999999',
            'type' => 'text',
            'status' => 'sent',
            'whatsapp_message_id' => 'wamid.mock1',
        ]);
    }

    #[Test]
    public function it_sends_using_named_account(): void
    {
        $this->createAccount('default', true);
        $this->createAccount('marketing', false);

        $message = WhatsApp::using('marketing')->send('923009999999', 'Sale Started');

        $this->assertSame('marketing', $message->account->name);
    }

    #[Test]
    public function it_logs_failed_messages(): void
    {
        $this->createAccount();

        $mock = $this->app->make(WhatsAppClientInterface::class);
        $mock->shouldFail = true;

        try {
            WhatsApp::sendText('923009999999', 'Will fail');
        } catch (\Throwable) {
            // expected
        }

        $this->assertDatabaseHas('whatsapp_messages', [
            'to' => '923009999999',
            'status' => 'failed',
        ]);
    }

    #[Test]
    public function it_sends_via_twilio_provider(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response([
                'sid' => 'SMtwilio123',
                'status' => 'queued',
            ], 201),
        ]);

        WhatsAppAccount::query()->create([
            'name' => 'twilio-default',
            'provider' => WhatsAppAccount::PROVIDER_TWILIO,
            'phone_number' => '923001234567',
            'twilio_sid' => 'AC999',
            'twilio_token' => 'twilio-token-1234567890',
            'twilio_whatsapp_number' => '14155238886',
            'is_default' => true,
            'is_active' => true,
        ]);

        $message = WhatsApp::send('923009999999', 'Hello via Twilio');

        $this->assertSame('sent', $message->status);
        $this->assertSame('SMtwilio123', $message->whatsapp_message_id);
    }
}
