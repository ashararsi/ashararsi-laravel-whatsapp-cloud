<?php

namespace Vendor\LaravelWhatsAppCloud\Events;

use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

class PhoneNumbersSynced
{
    public function __construct(
        public WhatsAppAccount $account,
        public int $count,
    ) {}
}
