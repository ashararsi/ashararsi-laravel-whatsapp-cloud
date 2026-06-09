<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Events\MessageDelivered;
use Vendor\LaravelWhatsAppCloud\Events\MessageReceived;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversationMessage;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class V12WebhookIdempotencyTest extends TestCase
{
    protected function createAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'idempotent',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => '12345',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function incomingPayload(string $messageId = 'wamid.duplicate.in'): array
    {
        return [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => '12345'],
                        'messages' => [[
                            'from' => '923009999999',
                            'id' => $messageId,
                            'type' => 'text',
                            'text' => ['body' => 'Duplicate test'],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    #[Test]
    public function it_ignores_duplicate_incoming_meta_messages(): void
    {
        Event::fake([MessageReceived::class]);

        $this->createAccount();

        $payload = $this->incomingPayload();

        $this->postJson('/whatsapp/webhook', $payload)->assertOk();
        $this->postJson('/whatsapp/webhook', $payload)->assertOk();

        $this->assertSame(1, WhatsAppMessage::query()->where('whatsapp_message_id', 'wamid.duplicate.in')->count());
        $this->assertSame(1, WhatsAppConversationMessage::query()->where('whatsapp_message_id', 'wamid.duplicate.in')->count());
        Event::assertDispatched(MessageReceived::class, 2);
    }

    #[Test]
    public function it_ignores_duplicate_status_updates(): void
    {
        Event::fake([MessageDelivered::class]);

        $account = $this->createAccount();

        WhatsAppMessage::query()->create([
            'account_id' => $account->id,
            'whatsapp_message_id' => 'wamid.status.dup',
            'to' => '923009999999',
            'type' => 'text',
            'message' => 'Hello',
            'status' => WhatsAppMessage::STATUS_DELIVERED,
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => '12345'],
                        'statuses' => [[
                            'id' => 'wamid.status.dup',
                            'recipient_id' => '923009999999',
                            'status' => 'delivered',
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/whatsapp/webhook', $payload)->assertOk();

        Event::assertNotDispatched(MessageDelivered::class);
        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_message_id' => 'wamid.status.dup',
            'status' => WhatsAppMessage::STATUS_DELIVERED,
        ]);
    }
}
