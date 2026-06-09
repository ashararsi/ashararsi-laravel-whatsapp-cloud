<?php

namespace Vendor\LaravelWhatsAppCloud\Support;

class WhatsAppPayload
{
    /**
     * @param  array<string, mixed>|null  $response
     */
    public static function extractMessageId(?array $response): ?string
    {
        if ($response === null) {
            return null;
        }

        $metaId = $response['messages'][0]['id'] ?? null;

        if (is_string($metaId) && $metaId !== '') {
            return $metaId;
        }

        $twilioSid = $response['sid'] ?? null;

        if (is_string($twilioSid) && $twilioSid !== '') {
            return $twilioSid;
        }

        return null;
    }
}
