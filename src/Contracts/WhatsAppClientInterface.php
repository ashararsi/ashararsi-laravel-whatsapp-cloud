<?php

namespace Vendor\LaravelWhatsAppCloud\Contracts;

interface WhatsAppClientInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function send(string $phoneNumberId, string $accessToken, array $payload): array;
}
