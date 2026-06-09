<?php

namespace Vendor\LaravelWhatsAppCloud\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMediaFile;

class MediaDownloaded
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $webhookMessage
     */
    public function __construct(
        public WhatsAppAccount $account,
        public WhatsAppMediaFile $file,
        public array $webhookMessage,
    ) {}
}
