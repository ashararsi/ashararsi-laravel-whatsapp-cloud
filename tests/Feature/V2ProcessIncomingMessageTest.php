<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Events\AutoReplyTriggered;
use Vendor\LaravelWhatsAppCloud\Events\MessageReceived;
use Vendor\LaravelWhatsAppCloud\Listeners\ProcessIncomingMessage;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAutoReply;
use Vendor\LaravelWhatsAppCloud\Tests\Mocks\MockWhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class V2ProcessIncomingMessageTest extends TestCase
{
    #[Test]
    public function it_processes_incoming_message_through_auto_reply_pipeline(): void
    {
        Event::fake([AutoReplyTriggered::class]);
        config([
            'whatsapp.events.process_incoming' => true,
            'whatsapp.media.enabled' => false,
            'whatsapp.auto_reply.enabled' => true,
            'whatsapp.ai.enabled' => false,
        ]);

        $this->app->instance(WhatsAppClientInterface::class, new MockWhatsAppClient);

        $account = WhatsAppAccount::query()->create([
            'name' => 'pipeline',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'pid',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        WhatsAppAutoReply::query()->create([
            'account_id' => $account->id,
            'name' => 'any',
            'trigger_type' => 'any',
            'trigger_value' => '*',
            'response' => 'Auto response',
            'is_active' => true,
        ]);

        app(ProcessIncomingMessage::class)->handle(new MessageReceived($account, [
            'from' => '923009999999',
            'id' => 'wamid.auto',
            'type' => 'text',
            'text' => ['body' => 'ping'],
        ]));

        $this->assertDatabaseHas('whatsapp_messages', [
            'to' => '923009999999',
            'message' => 'Auto response',
        ]);

        Event::assertDispatched(AutoReplyTriggered::class);
    }
}
