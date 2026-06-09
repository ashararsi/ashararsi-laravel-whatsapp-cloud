<?php

namespace Vendor\LaravelWhatsAppCloud\Support;

use Vendor\LaravelWhatsAppCloud\Services\WhatsAppMessageBuilder;

class TwilioIncomingMessageParser
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{phone: string, type: string, body: ?string, whatsapp_message_id: ?string, payload: array<string, mixed>}
     */
    public static function parse(array $payload): array
    {
        $from = self::stripWhatsAppPrefix((string) ($payload['From'] ?? ''));
        $messageSid = isset($payload['MessageSid']) ? (string) $payload['MessageSid'] : null;
        $numMedia = (int) ($payload['NumMedia'] ?? 0);

        $type = 'text';
        $body = $payload['Body'] ?? null;

        if ($numMedia > 0) {
            $type = 'image';
            $body = $body ?: '[Media message]';
        } elseif (isset($payload['Latitude'], $payload['Longitude'])) {
            $type = 'location';
            $body = sprintf('Location: %s, %s', $payload['Latitude'], $payload['Longitude']);
        }

        return [
            'phone' => WhatsAppMessageBuilder::normalizePhone($from),
            'type' => $type,
            'body' => is_string($body) ? $body : null,
            'whatsapp_message_id' => $messageSid,
            'payload' => $payload,
        ];
    }

    public static function stripWhatsAppPrefix(string $value): string
    {
        return preg_replace('/^whatsapp:/i', '', $value) ?? $value;
    }
}
