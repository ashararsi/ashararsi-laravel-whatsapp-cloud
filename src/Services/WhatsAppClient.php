<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;

class WhatsAppClient implements WhatsAppClientInterface
{
    public function send(string $phoneNumberId, string $accessToken, array $payload): array
    {
        $version = config('whatsapp.api_version', 'v21.0');
        $baseUrl = rtrim(config('whatsapp.api_base_url', 'https://graph.facebook.com'), '/');
        $url = "{$baseUrl}/{$version}/{$phoneNumberId}/messages";

        $response = $this->http($accessToken)->post($url, $payload);

        $data = $response->json() ?? [];

        if ($response->failed()) {
            $message = $data['error']['message'] ?? $response->body();

            throw new WhatsAppException(
                "WhatsApp API request failed: {$message}",
                $data,
                $response->status(),
            );
        }

        return $data;
    }

    protected function http(string $accessToken): PendingRequest
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }
}
