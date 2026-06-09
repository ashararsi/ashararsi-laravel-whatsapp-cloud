<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Events\CampaignCompleted;
use Vendor\LaravelWhatsAppCloud\Events\CampaignStarted;
use Vendor\LaravelWhatsAppCloud\Jobs\SendWhatsAppMessageJob;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppCampaign;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppCampaignRecipient;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;
use Vendor\LaravelWhatsAppCloud\Services\CampaignService;
use Vendor\LaravelWhatsAppCloud\Tests\Mocks\MockWhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class V2CampaignServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.queue_enabled' => false,
            'whatsapp.campaigns.use_queue' => false,
        ]);

        $this->app->forgetInstance(WhatsAppClientInterface::class);
    }

    protected function seedCampaign(): WhatsAppCampaign
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'campaign',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => '123',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $campaign = WhatsAppCampaign::query()->create([
            'account_id' => $account->id,
            'name' => 'Promo',
            'type' => 'text',
            'message' => 'Big sale!',
            'status' => WhatsAppCampaign::STATUS_DRAFT,
        ]);

        foreach (['923001111111', '923002222222'] as $phone) {
            $contact = WhatsAppContact::query()->create([
                'account_id' => $account->id,
                'phone' => $phone,
            ]);

            $campaign->recipients()->create([
                'contact_id' => $contact->id,
                'phone' => $phone,
            ]);
        }

        return $campaign->fresh();
    }

    #[Test]
    public function it_dispatches_campaign_and_sends_messages(): void
    {
        Event::fake([CampaignStarted::class, CampaignCompleted::class]);
        $this->app->instance(WhatsAppClientInterface::class, new MockWhatsAppClient);

        $campaign = $this->seedCampaign();

        app(CampaignService::class)->dispatch($campaign);

        $campaign->refresh();

        $this->assertSame(WhatsAppCampaign::STATUS_COMPLETED, $campaign->status);
        $this->assertSame(2, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);
        $this->assertDatabaseCount('whatsapp_messages', 2);

        Event::assertDispatched(CampaignStarted::class);
        Event::assertDispatched(CampaignCompleted::class);

        $this->assertSame(
            2,
            WhatsAppCampaignRecipient::query()->where('status', WhatsAppCampaignRecipient::STATUS_SENT)->count(),
        );
    }

    #[Test]
    public function it_can_queue_campaign_messages(): void
    {
        Queue::fake();
        config([
            'whatsapp.queue_enabled' => true,
            'whatsapp.campaigns.use_queue' => true,
        ]);

        $campaign = $this->seedCampaign();

        app(CampaignService::class)->dispatch($campaign);

        Queue::assertPushed(SendWhatsAppMessageJob::class, 2);
        $this->assertSame(2, $campaign->fresh()->sent_count);
    }

    #[Test]
    public function it_records_failed_recipients_without_crashing(): void
    {
        Event::fake([CampaignStarted::class, CampaignCompleted::class]);

        $mock = new MockWhatsAppClient;
        $mock->shouldFail = true;
        $this->app->instance(WhatsAppClientInterface::class, $mock);

        $campaign = $this->seedCampaign();

        app(CampaignService::class)->dispatch($campaign);

        $campaign->refresh();

        $this->assertSame(WhatsAppCampaign::STATUS_COMPLETED, $campaign->status);
        $this->assertSame(0, $campaign->sent_count);
        $this->assertSame(2, $campaign->failed_count);

        Event::assertDispatched(CampaignStarted::class);
        Event::assertDispatched(CampaignCompleted::class);
    }
}
