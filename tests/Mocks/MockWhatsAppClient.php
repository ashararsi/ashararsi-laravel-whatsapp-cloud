<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Mocks;

use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;

class MockWhatsAppClient implements WhatsAppClientInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $sent = [];

    public bool $shouldFail = false;

    protected int $messageCounter = 0;

    public function send(string $phoneNumberId, string $accessToken, array $payload): array
    {
        $this->sent[] = [
            'phone_number_id' => $phoneNumberId,
            'access_token' => $accessToken,
            'payload' => $payload,
        ];

        if ($this->shouldFail) {
            throw new WhatsAppException(
                'Mock API failure',
                ['error' => ['message' => 'Mock failure']],
                400,
            );
        }

        $this->messageCounter++;

        return [
            'messaging_product' => 'whatsapp',
            'contacts' => [['wa_id' => $payload['to'] ?? '']],
            'messages' => [['id' => 'wamid.mock'.$this->messageCounter]],
        ];
    }
}
