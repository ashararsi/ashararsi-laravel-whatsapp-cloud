<?php

namespace Vendor\LaravelWhatsAppCloud\Events;

use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppBusinessProfile;

class BusinessProfileSynced
{
    public function __construct(
        public WhatsAppAccount $account,
        public WhatsAppBusinessProfile $profile,
    ) {}
}
