<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Events\AiReplyGenerated;
use Vendor\LaravelWhatsAppCloud\Events\AutoReplyTriggered;
use Vendor\LaravelWhatsAppCloud\Events\CampaignCompleted;
use Vendor\LaravelWhatsAppCloud\Events\CampaignStarted;
use Vendor\LaravelWhatsAppCloud\Events\ContactCreated;
use Vendor\LaravelWhatsAppCloud\Events\ConversationReplied;
use Vendor\LaravelWhatsAppCloud\Events\MediaDownloaded;
use Vendor\LaravelWhatsAppCloud\Events\ScheduledMessageSent;
use Vendor\LaravelWhatsAppCloud\Events\TemplateSynced;
use Vendor\LaravelWhatsAppCloud\Events\TranscriptionCompleted;
use Vendor\LaravelWhatsAppCloud\Events\WorkflowExecuted;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAiWorkflow;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAutoReply;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppCampaign;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMediaFile;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppScheduledMessage;
use Vendor\LaravelWhatsAppCloud\Services\ConversationService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class V2EventsTest extends TestCase
{
    protected function metaAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'events',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'pid',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function conversation_replied_event_is_dispatchable(): void
    {
        Event::fake([ConversationReplied::class]);

        $account = $this->metaAccount();
        $service = new ConversationService;
        $service->recordIncoming($account, [
            'from' => '923009999999',
            'id' => 'wamid.1',
            'type' => 'text',
            'text' => ['body' => 'Hi'],
        ]);
        $conversation = $service->findOrCreateConversation(
            $account,
            WhatsAppContact::query()->firstOrFail(),
        );

        event(new ConversationReplied($conversation, 'Hello back'));

        Event::assertDispatched(ConversationReplied::class, function (ConversationReplied $event) {
            return $event->message === 'Hello back';
        });
    }

    #[Test]
    public function campaign_events_are_dispatchable(): void
    {
        Event::fake([CampaignStarted::class, CampaignCompleted::class]);

        $account = $this->metaAccount();
        $campaign = WhatsAppCampaign::query()->create([
            'account_id' => $account->id,
            'name' => 'Promo',
            'type' => 'text',
            'message' => 'Sale',
            'status' => WhatsAppCampaign::STATUS_DRAFT,
        ]);

        event(new CampaignStarted($campaign));
        event(new CampaignCompleted($campaign));

        Event::assertDispatched(CampaignStarted::class);
        Event::assertDispatched(CampaignCompleted::class);
    }

    #[Test]
    public function auto_reply_and_ai_events_are_dispatchable(): void
    {
        Event::fake([AutoReplyTriggered::class, AiReplyGenerated::class]);

        $account = $this->metaAccount();
        $rule = WhatsAppAutoReply::query()->create([
            'account_id' => $account->id,
            'name' => 'hours',
            'trigger_type' => 'keyword',
            'trigger_value' => 'hours',
            'response' => '9-5',
            'is_active' => true,
        ]);

        event(new AutoReplyTriggered($account, $rule, '923009999999', 'hours?'));
        event(new AiReplyGenerated($account, '923009999999', 'help', 'Sure'));

        Event::assertDispatched(AutoReplyTriggered::class);
        Event::assertDispatched(AiReplyGenerated::class);
    }

    #[Test]
    public function workflow_media_and_transcription_events_are_dispatchable(): void
    {
        Event::fake([WorkflowExecuted::class, MediaDownloaded::class, TranscriptionCompleted::class]);

        $account = $this->metaAccount();
        $workflow = WhatsAppAiWorkflow::query()->create([
            'account_id' => $account->id,
            'name' => 'greet',
            'is_active' => true,
        ]);
        $file = WhatsAppMediaFile::query()->create([
            'account_id' => $account->id,
            'media_id' => 'mid-1',
            'mime_type' => 'audio/ogg',
            'path' => 'whatsapp/media/1/mid-1',
        ]);

        event(new WorkflowExecuted($workflow, '923009999999', 'Hi', 'Welcome'));
        event(new MediaDownloaded($account, $file, ['type' => 'audio']));
        event(new TranscriptionCompleted($file, 'hello world'));

        Event::assertDispatched(WorkflowExecuted::class);
        Event::assertDispatched(MediaDownloaded::class);
        Event::assertDispatched(TranscriptionCompleted::class);
    }

    #[Test]
    public function scheduled_template_and_contact_events_are_dispatchable(): void
    {
        Event::fake([ScheduledMessageSent::class, TemplateSynced::class, ContactCreated::class]);

        $account = $this->metaAccount();
        $contact = WhatsAppContact::query()->create([
            'account_id' => $account->id,
            'phone' => '923009999999',
            'name' => 'User',
        ]);
        $scheduled = WhatsAppScheduledMessage::query()->create([
            'account_id' => $account->id,
            'to' => '923009999999',
            'message' => 'Later',
            'send_at' => now(),
        ]);

        event(new ScheduledMessageSent($scheduled));
        event(new TemplateSynced($account, 3));
        event(new ContactCreated($account, $contact));

        Event::assertDispatched(ScheduledMessageSent::class);
        Event::assertDispatched(TemplateSynced::class);
        Event::assertDispatched(ContactCreated::class);
    }
}
