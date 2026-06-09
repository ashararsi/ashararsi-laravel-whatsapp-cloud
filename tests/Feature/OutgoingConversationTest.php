<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Tests\Mocks\MockWhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class OutgoingConversationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(WhatsAppClientInterface::class, new MockWhatsAppClient);
    }

    #[Test]
    public function it_stores_outgoing_message_in_conversation(): void
    {
        WhatsAppAccount::query()->create([
            'name' => 'default',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => '123',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        WhatsApp::sendText('923009999999', 'Outbound hello');

        $this->assertDatabaseHas('whatsapp_contacts', ['phone' => '923009999999']);
        $this->assertDatabaseHas('whatsapp_conversation_messages', [
            'direction' => 'outgoing',
            'message' => 'Outbound hello',
            'whatsapp_message_id' => 'wamid.mock123',
        ]);
    }
}
