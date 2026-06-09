<?php

namespace Vendor\LaravelWhatsAppCloud\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

class TemplateSynced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WhatsAppAccount $account,
        public int $count,
    ) {}
}
