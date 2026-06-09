<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Events\MessageReceived;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class ConversationWebhookTest extends TestCase
{
    #[Test]
    public function it_stores_incoming_message_from_webhook(): void
    {
        Event::fake([MessageReceived::class]);

        WhatsAppAccount::query()->create([
            'name' => 'webhook',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => '12345',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => '12345'],
                        'contacts' => [
                            ['wa_id' => '923009999999', 'profile' => ['name' => 'Customer']],
                        ],
                        'messages' => [[
                            'from' => '923009999999',
                            'id' => 'wamid.webhook.in',
                            'type' => 'text',
                            'text' => ['body' => 'Need help'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/whatsapp/webhook', $payload)->assertOk();

        $this->assertDatabaseHas('whatsapp_contacts', [
            'phone' => '923009999999',
            'name' => 'Customer',
        ]);

        $this->assertDatabaseHas('whatsapp_conversation_messages', [
            'direction' => 'incoming',
            'whatsapp_message_id' => 'wamid.webhook.in',
            'message' => 'Need help',
        ]);
    }
}
