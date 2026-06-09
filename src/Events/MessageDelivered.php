<?php

namespace Vendor\LaravelWhatsAppCloud\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

class MessageDelivered
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $status
     */
    public function __construct(
        public WhatsAppAccount $account,
        public array $status,
    ) {}
}
