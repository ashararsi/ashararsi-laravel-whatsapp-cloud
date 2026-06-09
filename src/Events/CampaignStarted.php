<?php

namespace Vendor\LaravelWhatsAppCloud\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppCampaign;

class CampaignStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WhatsAppCampaign $campaign,
    ) {}
}
