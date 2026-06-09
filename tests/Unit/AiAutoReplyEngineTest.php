<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Events\AiReplyGenerated;
use Vendor\LaravelWhatsAppCloud\Jobs\SendWhatsAppMessageJob;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Services\AiAutoReplyEngine;
use Vendor\LaravelWhatsAppCloud\Tests\Mocks\MockWhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class AiAutoReplyEngineTest extends TestCase
{
    protected function metaAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'ai',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'pid',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_sends_ai_reply_and_dispatches_event(): void
    {
        Event::fake([AiReplyGenerated::class]);
        $this->app->instance(WhatsAppClientInterface::class, new MockWhatsAppClient);

        config([
            'whatsapp.ai.enabled' => true,
            'whatsapp.openai.api_key' => 'sk-test',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'AI says hi']]],
            ]),
        ]);

        $account = $this->metaAccount();
        $reply = app(AiAutoReplyEngine::class)->reply($account, '923009999999', 'Hello');

        $this->assertSame('AI says hi', $reply);
        $this->assertDatabaseHas('whatsapp_messages', [
            'to' => '923009999999',
            'message' => 'AI says hi',
            'status' => 'sent',
        ]);

        Event::assertDispatched(AiReplyGenerated::class, function (AiReplyGenerated $event) {
            return $event->reply === 'AI says hi';
        });
    }

    #[Test]
    public function it_respects_disabled_config(): void
    {
        Event::fake([AiReplyGenerated::class]);
        config(['whatsapp.ai.enabled' => false]);

        $this->assertNull(app(AiAutoReplyEngine::class)->reply($this->metaAccount(), '923009999999', 'Hi'));
        Event::assertNotDispatched(AiReplyGenerated::class);
    }

    #[Test]
    public function it_can_queue_ai_replies(): void
    {
        Queue::fake();
        config([
            'whatsapp.ai.enabled' => true,
            'whatsapp.ai.use_queue' => true,
            'whatsapp.queue_enabled' => true,
            'whatsapp.openai.api_key' => 'sk-test',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Queued AI']]],
            ]),
        ]);

        app(AiAutoReplyEngine::class)->reply($this->metaAccount(), '923009999999', 'Hello');

        Queue::assertPushed(SendWhatsAppMessageJob::class);
    }

    #[Test]
    public function it_handles_openai_failures_gracefully(): void
    {
        Event::fake([AiReplyGenerated::class]);
        config([
            'whatsapp.ai.enabled' => true,
            'whatsapp.openai.api_key' => 'sk-test',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'down'], 500),
        ]);

        $this->assertNull(app(AiAutoReplyEngine::class)->reply($this->metaAccount(), '923009999999', 'Hi'));
        Event::assertNotDispatched(AiReplyGenerated::class);
    }
}
