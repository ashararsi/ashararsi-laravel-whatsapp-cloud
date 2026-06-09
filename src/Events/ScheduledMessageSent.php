<?php

namespace Vendor\LaravelWhatsAppCloud\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppScheduledMessage;

class ScheduledMessageSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WhatsAppScheduledMessage $scheduled,
    ) {}
}
