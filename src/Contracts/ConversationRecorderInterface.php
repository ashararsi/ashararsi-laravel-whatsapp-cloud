<?php

namespace Vendor\LaravelWhatsAppCloud\Contracts;

use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversationMessage;

interface ConversationRecorderInterface
{
    /**
     * @param  array<string, mixed>  $webhookMessage
     * @param  array<int, array<string, mixed>>  $contacts
     */
    public function recordIncoming(
        WhatsAppAccount $account,
        array $webhookMessage,
        array $contacts = [],
    ): WhatsAppConversationMessage;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordOutgoing(
        WhatsAppAccount $account,
        string $phone,
        string $type,
        ?string $message,
        array $payload,
        ?string $whatsappMessageId = null,
    ): WhatsAppConversationMessage;
}
