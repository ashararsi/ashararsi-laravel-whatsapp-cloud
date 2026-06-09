<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Events\MessageDelivered;
use Vendor\LaravelWhatsAppCloud\Events\MessageRead;
use Vendor\LaravelWhatsAppCloud\Events\MessageReceived;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class WebhookTest extends TestCase
{
    #[Test]
    public function it_verifies_webhook_challenge_with_hub_dot_notation(): void
    {
        WhatsAppAccount::query()->create([
            'name' => 'webhook',
            'phone_number' => '923001234567',
            'phone_number_id' => '12345',
            'provider' => 'meta',
            'access_token' => 'token-1234567890',
            'webhook_verify_token' => 'my-verify-token',
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->get('/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=my-verify-token&hub.challenge=CHALLENGE123');

        $response->assertOk();
        $response->assertSee('CHALLENGE123');
    }

    #[Test]
    public function it_rejects_invalid_webhook_signature_when_required(): void
    {
        config([
            'whatsapp.webhook.app_secret' => 'secret-key',
            'whatsapp.webhook.require_signature' => true,
        ]);

        $response = $this->postJson('/whatsapp/webhook', ['entry' => []]);

        $response->assertForbidden();
    }

    #[Test]
    public function it_accepts_signed_webhook_payload(): void
    {
        config([
            'whatsapp.webhook.app_secret' => 'secret-key',
            'whatsapp.webhook.require_signature' => true,
        ]);

        WhatsAppAccount::query()->create([
            'name' => 'webhook',
            'phone_number' => '923001234567',
            'phone_number_id' => '12345',
            'provider' => 'meta',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => ['phone_number_id' => '12345'],
                                'messages' => [
                                    ['from' => '923009999999', 'type' => 'text', 'text' => ['body' => 'Hi']],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'secret-key');

        $response = $this->call(
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
        );

        $response->assertOk();
    }

    #[Test]
    public function it_dispatches_events_for_incoming_webhook_payload(): void
    {
        Event::fake([MessageReceived::class, MessageDelivered::class, MessageRead::class]);

        WhatsAppAccount::query()->create([
            'name' => 'webhook',
            'phone_number' => '923001234567',
            'phone_number_id' => '12345',
            'provider' => 'meta',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => ['phone_number_id' => '12345'],
                                'messages' => [
                                    ['from' => '923009999999', 'type' => 'text', 'text' => ['body' => 'Hi']],
                                ],
                                'statuses' => [
                                    ['id' => 'wamid.abc', 'recipient_id' => '923009999999', 'status' => 'delivered'],
                                    ['id' => 'wamid.abc', 'recipient_id' => '923009999999', 'status' => 'read'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/whatsapp/webhook', $payload);

        $response->assertOk();
        Event::assertDispatched(MessageReceived::class);
        Event::assertDispatched(MessageDelivered::class);
        Event::assertDispatched(MessageRead::class);
    }

    #[Test]
    public function it_updates_message_status_by_whatsapp_message_id(): void
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'webhook',
            'phone_number' => '923001234567',
            'phone_number_id' => '12345',
            'provider' => 'meta',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        WhatsAppMessage::query()->create([
            'account_id' => $account->id,
            'whatsapp_message_id' => 'wamid.abc',
            'to' => '923009999999',
            'type' => 'text',
            'message' => 'Hello',
            'status' => WhatsAppMessage::STATUS_SENT,
        ]);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => ['phone_number_id' => '12345'],
                                'statuses' => [
                                    ['id' => 'wamid.abc', 'recipient_id' => '923009999999', 'status' => 'delivered'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson('/whatsapp/webhook', $payload)->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_message_id' => 'wamid.abc',
            'status' => WhatsAppMessage::STATUS_DELIVERED,
        ]);
    }
}
