<?php

namespace Vendor\LaravelWhatsAppCloud\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversation;

class ConversationReplied
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WhatsAppConversation $conversation,
        public string $message,
    ) {}
}
