<?php

namespace Vendor\LaravelWhatsAppCloud\Commands;

use Illuminate\Console\Command;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppCampaign;
use Vendor\LaravelWhatsAppCloud\Services\CampaignService;

class RunCampaignsCommand extends Command
{
    protected $signature = 'whatsapp:campaigns:run {--id= : Campaign ID}';

    protected $description = 'Dispatch pending or scheduled WhatsApp broadcast campaigns';

    public function handle(CampaignService $campaigns): int
    {
        $query = WhatsAppCampaign::query()
            ->whereIn('status', [WhatsAppCampaign::STATUS_DRAFT, WhatsAppCampaign::STATUS_SCHEDULED])
            ->when($this->option('id'), fn ($q, $id) => $q->where('id', $id));

        $count = 0;

        foreach ($query->cursor() as $campaign) {
            $campaigns->dispatch($campaign);
            $count++;
            $this->info("Campaign [{$campaign->name}] dispatched.");
        }

        $this->info("Processed {$count} campaign(s).");

        return self::SUCCESS;
    }
}
