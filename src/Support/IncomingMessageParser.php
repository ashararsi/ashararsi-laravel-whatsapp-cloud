<?php

namespace Vendor\LaravelWhatsAppCloud\Support;

use Vendor\LaravelWhatsAppCloud\Services\WhatsAppMessageBuilder;

class IncomingMessageParser
{
    /**
     * @param  array<string, mixed>  $message
     * @param  array<int, array<string, mixed>>  $contacts
     * @return array{phone: string, name: ?string, type: string, body: ?string, whatsapp_message_id: ?string, payload: array<string, mixed>}
     */
    public static function parse(array $message, array $contacts = []): array
    {
        $phone = (string) ($message['from'] ?? '');
        $type = (string) ($message['type'] ?? 'unknown');
        $whatsappMessageId = isset($message['id']) ? (string) $message['id'] : null;

        $name = self::resolveContactName($phone, $contacts);

        return [
            'phone' => WhatsAppMessageBuilder::normalizePhone($phone),
            'name' => $name,
            'type' => $type,
            'body' => self::extractBody($message, $type),
            'whatsapp_message_id' => $whatsappMessageId,
            'payload' => $message,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $contacts
     */
    protected static function resolveContactName(string $phone, array $contacts): ?string
    {
        $normalized = WhatsAppMessageBuilder::normalizePhone($phone);

        foreach ($contacts as $contact) {
            $waId = isset($contact['wa_id']) ? WhatsAppMessageBuilder::normalizePhone((string) $contact['wa_id']) : null;

            if ($waId === $normalized) {
                $name = $contact['profile']['name'] ?? null;

                return is_string($name) && $name !== '' ? $name : null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected static function extractBody(array $message, string $type): ?string
    {
        return match ($type) {
            'text' => $message['text']['body'] ?? null,
            'button' => $message['button']['text'] ?? null,
            'interactive' => $message['interactive']['button_reply']['title']
                ?? $message['interactive']['list_reply']['title']
                ?? null,
            'image' => $message['image']['caption'] ?? '[Image]',
            'document' => $message['document']['filename'] ?? '[Document]',
            'audio' => '[Audio]',
            'video' => $message['video']['caption'] ?? '[Video]',
            'location' => isset($message['location']['name'])
                ? (string) $message['location']['name']
                : '[Location]',
            'sticker' => '[Sticker]',
            default => '['.ucfirst($type).' message]',
        };
    }
}
