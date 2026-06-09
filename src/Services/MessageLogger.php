<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Vendor\LaravelWhatsAppCloud\Contracts\MessageLoggerInterface;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Support\WhatsAppPayload;

class MessageLogger implements MessageLoggerInterface
{
    public function log(
        WhatsAppAccount $account,
        string $to,
        string $type,
        ?string $message,
        array $payload,
        ?array $response,
        string $status,
        ?string $whatsappMessageId = null,
    ): WhatsAppMessage {
        $whatsappMessageId ??= WhatsAppPayload::extractMessageId($response);

        $attributes = [
            'account_id' => $account->id,
            'to' => WhatsAppMessageBuilder::normalizePhone($to),
            'type' => $type,
            'message' => $message,
            'status' => $status,
            'meta_json' => $this->sanitizePayload($payload),
            'response_json' => $response,
            'whatsapp_message_id' => $whatsappMessageId,
        ];

        if (! config('whatsapp.log_messages', true)) {
            return new WhatsAppMessage($attributes);
        }

        return WhatsAppMessage::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function sanitizePayload(array $payload): array
    {
        return $payload;
    }
}
