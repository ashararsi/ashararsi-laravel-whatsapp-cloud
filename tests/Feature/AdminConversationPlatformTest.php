<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversation;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversationMessage;
use Vendor\LaravelWhatsAppCloud\Services\ConversationService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class AdminConversationPlatformTest extends TestCase
{
    protected function seedConversation(): WhatsAppConversation
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'primary',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => '123',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $service = new ConversationService;
        $service->recordIncoming($account, [
            'from' => '923009999999',
            'id' => 'wamid.in',
            'type' => 'text',
            'text' => ['body' => 'Hello'],
        ], [
            ['wa_id' => '923009999999', 'profile' => ['name' => 'Test User']],
        ]);

        $service->recordOutgoing($account, '923009999999', 'text', 'Hi there', [], 'wamid.out');

        return WhatsAppConversation::query()->firstOrFail();
    }

    #[Test]
    public function it_shows_dashboard_with_stats(): void
    {
        $this->seedConversation();

        $response = $this->get('/admin/whatsapp');

        $response->assertOk();
        $response->assertSee('Total Contacts');
        $response->assertSee('Incoming Today');
    }

    #[Test]
    public function it_lists_and_searches_contacts(): void
    {
        $this->seedConversation();

        $this->get('/admin/whatsapp/contacts')->assertOk()->assertSee('Test User');
        $this->get('/admin/whatsapp/contacts?search=999999')->assertOk()->assertSee('923009999999');
        $this->get('/admin/whatsapp/contacts?search=missing')->assertOk()->assertDontSee('Test User');
    }

    #[Test]
    public function it_shows_contact_detail_page(): void
    {
        $conversation = $this->seedConversation();
        $contact = $conversation->contact;

        $this->get("/admin/whatsapp/contacts/{$contact->id}")
            ->assertOk()
            ->assertSee('Test User')
            ->assertSee('923009999999');
    }

    #[Test]
    public function it_lists_conversations_and_shows_timeline(): void
    {
        $conversation = $this->seedConversation();

        $this->get('/admin/whatsapp/conversations')
            ->assertOk()
            ->assertSee('Test User');

        $this->get("/admin/whatsapp/conversations/{$conversation->id}")
            ->assertOk()
            ->assertSee('Hello')
            ->assertSee('Hi there')
            ->assertSee('incoming')
            ->assertSee('outgoing');
    }
}
