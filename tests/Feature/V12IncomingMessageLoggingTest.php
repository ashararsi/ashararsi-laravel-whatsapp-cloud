<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class V12IncomingMessageLoggingTest extends TestCase
{
    #[Test]
    public function it_logs_incoming_messages_to_whatsapp_messages_table(): void
    {
        WhatsAppAccount::query()->create([
            'name' => 'logger',
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
                        'messages' => [[
                            'from' => '923009999999',
                            'id' => 'wamid.log.in',
                            'type' => 'text',
                            'text' => ['body' => 'Logged incoming'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/whatsapp/webhook', $payload)->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_message_id' => 'wamid.log.in',
            'direction' => WhatsAppMessage::DIRECTION_INCOMING,
            'from' => '923009999999',
            'type' => 'text',
            'message' => 'Logged incoming',
            'status' => WhatsAppMessage::STATUS_RECEIVED,
        ]);
    }
}
