<?php

namespace Vendor\LaravelWhatsAppCloud\Events;

use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

class MessageDeadLettered
{
    public function __construct(
        public WhatsAppAccount $account,
        public string $to,
        public string $type,
        public ?int $messageId,
        public string $error,
    ) {}
}
