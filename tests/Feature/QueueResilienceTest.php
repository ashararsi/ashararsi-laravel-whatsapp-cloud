<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Events\MessageDeadLettered;
use Vendor\LaravelWhatsAppCloud\Events\MessageSendFailed;
use Vendor\LaravelWhatsAppCloud\Jobs\SendWhatsAppMessageJob;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class QueueResilienceTest extends TestCase
{
    #[Test]
    public function job_has_configurable_retry_strategy(): void
    {
        config(['whatsapp.queue.tries' => 5, 'whatsapp.queue.backoff' => [5, 15, 45]]);

        $job = new SendWhatsAppMessageJob(
            accountId: 1,
            payload: [],
            type: 'text',
            to: '923001234567',
        );

        $this->assertSame(5, $job->tries);
        $this->assertSame([5, 15, 45], $job->backoff);
    }

    #[Test]
    public function failed_job_marks_message_dead_lettered_and_fires_event(): void
    {
        Event::fake([MessageDeadLettered::class]);

        $account = WhatsAppAccount::query()->create([
            'name' => 'dlq',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'id',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $message = WhatsAppMessage::query()->create([
            'account_id' => $account->id,
            'to' => '923009999999',
            'type' => 'text',
            'message' => 'fail',
            'status' => WhatsAppMessage::STATUS_PENDING,
        ]);

        $job = new SendWhatsAppMessageJob(
            accountId: $account->id,
            payload: ['type' => 'text'],
            type: 'text',
            to: '923009999999',
            messageId: $message->id,
        );

        $job->failed(new \RuntimeException('Permanent failure'));

        $message->refresh();
        $this->assertSame(WhatsAppMessage::STATUS_FAILED, $message->status);
        $this->assertNotNull($message->dead_lettered_at);
        $this->assertSame('Permanent failure', $message->last_error);

        Event::assertDispatched(MessageDeadLettered::class);
    }

    #[Test]
    public function message_send_failed_event_exists(): void
    {
        $this->assertTrue(class_exists(MessageSendFailed::class));
    }
}
