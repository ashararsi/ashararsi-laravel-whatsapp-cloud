<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Events\AutoReplyTriggered;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAutoReply;
use Vendor\LaravelWhatsAppCloud\Services\AutoReplyEngine;
use Vendor\LaravelWhatsAppCloud\Services\ConversationService;
use Vendor\LaravelWhatsAppCloud\Tests\Mocks\MockWhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class AutoReplyEngineTest extends TestCase
{
    protected function metaAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'auto',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'pid',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_sends_keyword_auto_reply_and_dispatches_event(): void
    {
        Event::fake([AutoReplyTriggered::class]);
        $this->app->instance(WhatsAppClientInterface::class, new MockWhatsAppClient);
        config(['whatsapp.auto_reply.enabled' => true, 'whatsapp.ai.enabled' => false]);

        $account = $this->metaAccount();
        WhatsAppAutoReply::query()->create([
            'account_id' => $account->id,
            'name' => 'hours',
            'trigger_type' => 'keyword',
            'trigger_value' => 'hours',
            'response' => '9am-5pm',
            'is_active' => true,
        ]);

        $reply = app(AutoReplyEngine::class)->handleIncoming($account, '923009999999', 'What are your hours?');

        $this->assertSame('9am-5pm', $reply);
        $this->assertDatabaseHas('whatsapp_messages', [
            'to' => '923009999999',
            'message' => '9am-5pm',
        ]);

        Event::assertDispatched(AutoReplyTriggered::class);
    }

    #[Test]
    public function it_matches_first_message_trigger(): void
    {
        Event::fake([AutoReplyTriggered::class]);
        $this->app->instance(WhatsAppClientInterface::class, new MockWhatsAppClient);
        config(['whatsapp.auto_reply.enabled' => true]);

        $account = $this->metaAccount();
        WhatsAppAutoReply::query()->create([
            'account_id' => $account->id,
            'name' => 'welcome',
            'trigger_type' => WhatsAppAutoReply::TRIGGER_FIRST_MESSAGE,
            'trigger_value' => '*',
            'response' => 'Welcome!',
            'is_active' => true,
        ]);

        $reply = app(AutoReplyEngine::class)->handleIncoming($account, '923009999999', 'Hi', isFirstMessage: true);

        $this->assertSame('Welcome!', $reply);
        Event::assertDispatched(AutoReplyTriggered::class);
    }

    #[Test]
    public function it_returns_null_when_disabled(): void
    {
        Event::fake([AutoReplyTriggered::class]);
        config(['whatsapp.auto_reply.enabled' => false]);

        $this->assertNull(app(AutoReplyEngine::class)->handleIncoming($this->metaAccount(), '923009999999', 'Hi'));
        Event::assertNotDispatched(AutoReplyTriggered::class);
    }

    #[Test]
    public function it_detects_first_incoming_message(): void
    {
        $account = $this->metaAccount();
        $service = new ConversationService;
        $service->recordIncoming($account, [
            'from' => '923009999999',
            'id' => 'wamid.1',
            'type' => 'text',
            'text' => ['body' => 'First'],
        ]);

        $engine = app(AutoReplyEngine::class);

        $this->assertTrue($engine->isFirstIncomingMessage($account, '923009999999'));
    }
}
