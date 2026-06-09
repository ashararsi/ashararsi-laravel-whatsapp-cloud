<?php

namespace Vendor\LaravelWhatsAppCloud\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;

class ContactCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WhatsAppAccount $account,
        public WhatsAppContact $contact,
    ) {}
}
