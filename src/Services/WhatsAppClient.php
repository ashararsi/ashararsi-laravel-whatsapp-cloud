<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;

class WhatsAppClient implements WhatsAppClientInterface
{
    public function __construct(
        protected GraphApiClient $graphApi,
    ) {}

    public function send(string $phoneNumberId, string $accessToken, array $payload): array
    {
        return $this->graphApi->post($accessToken, "{$phoneNumberId}/messages", $payload);
    }
}
