<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Events\MessageReceived;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Services\TwilioSignatureValidator;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class TwilioWebhookTest extends TestCase
{
    protected function createTwilioAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'twilio',
            'provider' => WhatsAppAccount::PROVIDER_TWILIO,
            'phone_number' => '14155238886',
            'twilio_sid' => 'ACtest123',
            'twilio_token' => 'twilio-auth-token',
            'twilio_whatsapp_number' => '14155238886',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function postTwilio(string $uri, array $payload, ?string $authToken = 'twilio-auth-token'): TestResponse
    {
        config(['whatsapp.twilio.require_signature' => true]);

        $url = url($uri);
        $signature = app(TwilioSignatureValidator::class)->computeSignature($url, $payload, (string) $authToken);

        return $this->post($uri, $payload, [
            'X-Twilio-Signature' => $signature,
        ]);
    }

    #[Test]
    public function it_stores_twilio_inbound_text_message(): void
    {
        Event::fake([MessageReceived::class]);

        $this->createTwilioAccount();

        $payload = [
            'AccountSid' => 'ACtest123',
            'From' => 'whatsapp:+923009999999',
            'To' => 'whatsapp:+14155238886',
            'Body' => 'Twilio hello',
            'MessageSid' => 'SMincoming001',
            'NumMedia' => '0',
        ];

        $this->postTwilio('/whatsapp/twilio/webhook', $payload)->assertOk();

        $this->assertDatabaseHas('whatsapp_contacts', [
            'phone' => '923009999999',
        ]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_message_id' => 'SMincoming001',
            'direction' => WhatsAppMessage::DIRECTION_INCOMING,
            'from' => '923009999999',
            'message' => 'Twilio hello',
            'status' => WhatsAppMessage::STATUS_RECEIVED,
        ]);

        $this->assertDatabaseHas('whatsapp_conversation_messages', [
            'whatsapp_message_id' => 'SMincoming001',
            'direction' => 'incoming',
            'message' => 'Twilio hello',
        ]);

        Event::assertDispatched(MessageReceived::class);
    }

    #[Test]
    public function it_stores_twilio_inbound_media_message(): void
    {
        $this->createTwilioAccount();

        $payload = [
            'AccountSid' => 'ACtest123',
            'From' => 'whatsapp:+923009999999',
            'To' => 'whatsapp:+14155238886',
            'Body' => '',
            'MessageSid' => 'SMincoming002',
            'NumMedia' => '1',
            'MediaUrl0' => 'https://api.twilio.com/media/abc',
            'MediaContentType0' => 'image/jpeg',
        ];

        $this->postTwilio('/whatsapp/twilio/webhook', $payload)->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_message_id' => 'SMincoming002',
            'type' => 'image',
            'direction' => WhatsAppMessage::DIRECTION_INCOMING,
        ]);
    }

    #[Test]
    public function it_stores_twilio_inbound_location_message(): void
    {
        $this->createTwilioAccount();

        $payload = [
            'AccountSid' => 'ACtest123',
            'From' => 'whatsapp:+923009999999',
            'To' => 'whatsapp:+14155238886',
            'MessageSid' => 'SMincoming003',
            'NumMedia' => '0',
            'Latitude' => '24.8607',
            'Longitude' => '67.0011',
        ];

        $this->postTwilio('/whatsapp/twilio/webhook', $payload)->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_message_id' => 'SMincoming003',
            'type' => 'location',
            'direction' => WhatsAppMessage::DIRECTION_INCOMING,
        ]);
    }

    #[Test]
    public function it_updates_twilio_message_status_callbacks(): void
    {
        $account = $this->createTwilioAccount();

        WhatsAppMessage::query()->create([
            'account_id' => $account->id,
            'whatsapp_message_id' => 'SMstatus001',
            'to' => '923009999999',
            'type' => 'text',
            'message' => 'Outbound',
            'status' => WhatsAppMessage::STATUS_PENDING,
            'direction' => WhatsAppMessage::DIRECTION_OUTGOING,
        ]);

        foreach (['queued' => 'pending', 'sent' => 'sent', 'delivered' => 'delivered'] as $twilioStatus => $dbStatus) {
            $payload = [
                'AccountSid' => 'ACtest123',
                'MessageSid' => 'SMstatus001',
                'MessageStatus' => $twilioStatus,
            ];

            $this->postTwilio('/whatsapp/twilio/status', $payload)->assertOk();

            $this->assertDatabaseHas('whatsapp_messages', [
                'whatsapp_message_id' => 'SMstatus001',
                'status' => $dbStatus,
            ]);
        }

        $failedPayload = [
            'AccountSid' => 'ACtest123',
            'MessageSid' => 'SMstatus001',
            'MessageStatus' => 'failed',
        ];

        $this->postTwilio('/whatsapp/twilio/status', $failedPayload)->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_message_id' => 'SMstatus001',
            'status' => WhatsAppMessage::STATUS_FAILED,
        ]);
    }

    #[Test]
    public function it_rejects_invalid_twilio_signature(): void
    {
        $this->createTwilioAccount();

        config(['whatsapp.twilio.require_signature' => true]);

        $this->post('/whatsapp/twilio/webhook', [
            'AccountSid' => 'ACtest123',
            'From' => 'whatsapp:+923009999999',
            'Body' => 'Hi',
            'MessageSid' => 'SMbad',
        ], [
            'X-Twilio-Signature' => 'invalid',
        ])->assertForbidden();
    }
}
