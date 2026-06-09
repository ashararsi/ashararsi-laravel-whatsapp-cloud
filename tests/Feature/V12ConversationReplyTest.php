<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Events\ConversationReplied;
use Vendor\LaravelWhatsAppCloud\Jobs\SendWhatsAppMessageJob;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversation;
use Vendor\LaravelWhatsAppCloud\Services\ConversationService;
use Vendor\LaravelWhatsAppCloud\Tests\Mocks\MockWhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class V12ConversationReplyTest extends TestCase
{
    protected function seedConversation(): WhatsAppConversation
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'reply',
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
        ]);

        return $service->findOrCreateConversation(
            $account,
            WhatsAppContact::query()->firstOrFail(),
        );
    }

    #[Test]
    public function it_sends_reply_from_conversation_page(): void
    {
        Event::fake([ConversationReplied::class]);
        $this->app->instance(WhatsAppClientInterface::class, new MockWhatsAppClient);

        $conversation = $this->seedConversation();

        $this->post("/admin/whatsapp/conversations/{$conversation->id}/reply", [
            'message' => 'We can help',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_messages', [
            'account_id' => $conversation->account_id,
            'to' => '923009999999',
            'direction' => 'outgoing',
            'message' => 'We can help',
            'status' => 'sent',
        ]);

        Event::assertDispatched(ConversationReplied::class, function (ConversationReplied $event) {
            return $event->message === 'We can help';
        });
    }

    #[Test]
    public function it_can_queue_conversation_reply(): void
    {
        Queue::fake();
        config(['whatsapp.queue_enabled' => true]);

        $conversation = $this->seedConversation();

        $this->post("/admin/whatsapp/conversations/{$conversation->id}/reply", [
            'message' => 'Queued reply',
            'queue' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        Queue::assertPushed(SendWhatsAppMessageJob::class);
    }

    #[Test]
    public function it_handles_reply_send_failures_gracefully(): void
    {
        $mock = new MockWhatsAppClient;
        $mock->shouldFail = true;
        $this->app->instance(WhatsAppClientInterface::class, $mock);

        $conversation = $this->seedConversation();

        $this->post("/admin/whatsapp/conversations/{$conversation->id}/reply", [
            'message' => 'This will fail',
        ])->assertRedirect()->assertSessionHas('error');
    }
}
