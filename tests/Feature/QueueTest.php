<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;
use Vendor\LaravelWhatsAppCloud\Jobs\SendWhatsAppMessageJob;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Tests\Mocks\MockWhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class QueueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['whatsapp.queue_enabled' => true]);
        $this->app->instance(WhatsAppClientInterface::class, new MockWhatsAppClient);
    }

    #[Test]
    public function it_dispatches_queue_job_when_using_queue_helper(): void
    {
        Queue::fake();

        WhatsAppAccount::query()->create([
            'name' => 'default',
            'phone_number' => '923001234567',
            'phone_number_id' => '123',
            'provider' => 'meta',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        WhatsApp::queue()->send('923009999999', 'Queued message');

        Queue::assertPushed(SendWhatsAppMessageJob::class, function (SendWhatsAppMessageJob $job) {
            return $job->to === '923009999999' && $job->type === 'text';
        });
    }
}
