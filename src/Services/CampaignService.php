<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Vendor\LaravelWhatsAppCloud\Events\CampaignCompleted;
use Vendor\LaravelWhatsAppCloud\Events\CampaignStarted;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppCampaign;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppCampaignRecipient;

class CampaignService
{
    public function dispatch(WhatsAppCampaign $campaign): void
    {
        $campaign->update(['status' => WhatsAppCampaign::STATUS_RUNNING]);
        event(new CampaignStarted($campaign));

        $account = $campaign->account;
        $useQueue = config('whatsapp.campaigns.use_queue', false)
            && config('whatsapp.queue_enabled', true);

        $recipients = $campaign->recipients()
            ->where('status', WhatsAppCampaignRecipient::STATUS_PENDING)
            ->get();

        foreach ($recipients as $recipient) {
            try {
                $sender = WhatsApp::account($account->id);

                if ($useQueue) {
                    $sender = $sender->queue();
                }

                $sender->sendText($recipient->phone, (string) $campaign->message);

                $recipient->update([
                    'status' => WhatsAppCampaignRecipient::STATUS_SENT,
                    'sent_at' => now(),
                ]);

                $campaign->increment('sent_count');
            } catch (\Throwable $e) {
                $recipient->update([
                    'status' => WhatsAppCampaignRecipient::STATUS_FAILED,
                    'response_json' => ['error' => $e->getMessage()],
                ]);

                $campaign->increment('failed_count');
            }
        }

        $campaign->update(['status' => WhatsAppCampaign::STATUS_COMPLETED]);
        event(new CampaignCompleted($campaign->fresh()));
    }
}
