<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversation;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversationMessage;
use Vendor\LaravelWhatsAppCloud\Services\ConversationService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class ConversationServiceTest extends TestCase
{
    protected function createAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'meta',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'phone-id',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_records_incoming_message_and_creates_contact_conversation(): void
    {
        $account = $this->createAccount();
        $service = new ConversationService;

        $service->recordIncoming($account, [
            'from' => '923009999999',
            'id' => 'wamid.in1',
            'type' => 'text',
            'text' => ['body' => 'Hi'],
        ], [
            ['wa_id' => '923009999999', 'profile' => ['name' => 'Sara']],
        ]);

        $this->assertDatabaseHas('whatsapp_contacts', [
            'account_id' => $account->id,
            'phone' => '923009999999',
            'name' => 'Sara',
        ]);

        $contact = WhatsAppContact::query()->where('phone', '923009999999')->first();
        $this->assertNotNull($contact);

        $this->assertDatabaseHas('whatsapp_conversations', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertDatabaseHas('whatsapp_conversation_messages', [
            'direction' => 'incoming',
            'whatsapp_message_id' => 'wamid.in1',
            'message' => 'Hi',
        ]);
    }

    #[Test]
    public function it_records_outgoing_message(): void
    {
        $account = $this->createAccount();
        $service = new ConversationService;

        $service->recordOutgoing(
            $account,
            '923009999999',
            'text',
            'Hello back',
            ['type' => 'text'],
            'wamid.out1',
        );

        $this->assertDatabaseHas('whatsapp_conversation_messages', [
            'direction' => 'outgoing',
            'whatsapp_message_id' => 'wamid.out1',
            'message' => 'Hello back',
        ]);

        $conversation = WhatsAppConversation::query()->first();
        $this->assertNotNull($conversation->last_message_at);
    }

    #[Test]
    public function it_updates_existing_contact_on_repeat_incoming(): void
    {
        $account = $this->createAccount();
        $service = new ConversationService;

        $service->recordIncoming($account, [
            'from' => '923009999999',
            'id' => 'wamid.1',
            'type' => 'text',
            'text' => ['body' => 'First'],
        ]);

        $service->recordIncoming($account, [
            'from' => '923009999999',
            'id' => 'wamid.2',
            'type' => 'text',
            'text' => ['body' => 'Second'],
        ]);

        $this->assertSame(1, WhatsAppContact::query()->count());
        $this->assertSame(1, WhatsAppConversation::query()->count());
        $this->assertSame(2, WhatsAppConversationMessage::query()->count());
    }
}
