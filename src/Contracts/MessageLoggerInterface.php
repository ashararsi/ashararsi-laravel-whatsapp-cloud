<?php

namespace Vendor\LaravelWhatsAppCloud\Contracts;

use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;

interface MessageLoggerInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $response
     */
    public function log(
        WhatsAppAccount $account,
        string $to,
        string $type,
        ?string $message,
        array $payload,
        ?array $response,
        string $status,
        ?string $whatsappMessageId = null,
    ): WhatsAppMessage;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function logIncoming(
        WhatsAppAccount $account,
        string $from,
        string $type,
        ?string $message,
        array $payload,
        ?string $whatsappMessageId = null,
    ): WhatsAppMessage;
}
