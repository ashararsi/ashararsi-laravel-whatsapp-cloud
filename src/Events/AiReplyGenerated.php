<?php

namespace Vendor\LaravelWhatsAppCloud\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

class AiReplyGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WhatsAppAccount $account,
        public string $phone,
        public string $incomingMessage,
        public string $reply,
    ) {}
}
