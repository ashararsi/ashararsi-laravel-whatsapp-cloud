<?php

namespace Vendor\LaravelWhatsAppCloud\Providers;

use Illuminate\Support\Facades\Http;
use Vendor\LaravelWhatsAppCloud\Contracts\SupportsInteractiveMessages;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppProviderInterface;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppMessageBuilder;
use Vendor\LaravelWhatsAppCloud\Support\ProviderResult;

class TwilioProvider implements SupportsInteractiveMessages, WhatsAppProviderInterface
{
    public function __construct(
        protected WhatsAppAccount $account,
    ) {}

    public function sendText(string $to, string $text, bool $previewUrl = false): ProviderResult
    {
        $payload = [
            'From' => $this->fromAddress(),
            'To' => $this->toAddress($to),
            'Body' => $text,
        ];

        return $this->dispatch($payload);
    }

    public function sendTemplate(
        string $to,
        string $name,
        string $language = 'en_US',
        array $components = [],
    ): ProviderResult {
        $payload = [
            'From' => $this->fromAddress(),
            'To' => $this->toAddress($to),
            'ContentSid' => $name,
        ];

        if ($components !== []) {
            $payload['ContentVariables'] = json_encode($components, JSON_THROW_ON_ERROR);
        }

        return $this->dispatch($payload);
    }

    public function sendImage(string $to, string $link, ?string $caption = null): ProviderResult
    {
        return $this->sendMedia($to, $link, $caption);
    }

    public function sendDocument(
        string $to,
        string $link,
        ?string $filename = null,
        ?string $caption = null,
    ): ProviderResult {
        return $this->sendMedia($to, $link, $caption);
    }

    public function sendVideo(string $to, string $link, ?string $caption = null): ProviderResult
    {
        return $this->sendMedia($to, $link, $caption);
    }

    public function sendAudio(string $to, string $link): ProviderResult
    {
        return $this->sendMedia($to, $link);
    }

    public function sendLocation(
        string $to,
        float $latitude,
        float $longitude,
        ?string $name = null,
        ?string $address = null,
    ): ProviderResult {
        $label = $name ?? 'Location';
        $body = sprintf('%s: %s, %s', $label, $latitude, $longitude);

        if ($address) {
            $body .= " ({$address})";
        }

        return $this->sendText($to, $body);
    }

    public function buildPayload(string $type, string $to, array $options = []): array
    {
        $twilio = match ($type) {
            'text' => [
                'From' => $this->fromAddress(),
                'To' => $this->toAddress($to),
                'Body' => (string) ($options['text'] ?? ''),
            ],
            'template' => array_filter([
                'From' => $this->fromAddress(),
                'To' => $this->toAddress($to),
                'ContentSid' => (string) ($options['name'] ?? ''),
                'ContentVariables' => isset($options['components']) && $options['components'] !== []
                    ? json_encode($options['components'], JSON_THROW_ON_ERROR)
                    : null,
            ]),
            'image', 'document', 'video', 'audio' => array_filter([
                'From' => $this->fromAddress(),
                'To' => $this->toAddress($to),
                'MediaUrl' => (string) ($options['link'] ?? ''),
                'Body' => $options['caption'] ?? null,
            ]),
            'location' => [
                'From' => $this->fromAddress(),
                'To' => $this->toAddress($to),
                'Body' => sprintf(
                    '%s: %s, %s',
                    $options['name'] ?? 'Location',
                    $options['latitude'] ?? 0,
                    $options['longitude'] ?? 0,
                ),
            ],
            'buttons', 'list' => [
                'From' => $this->fromAddress(),
                'To' => $this->toAddress($to),
                'Body' => (string) ($options['body'] ?? ''),
            ],
            default => throw new WhatsAppException("Unsupported Twilio payload type [{$type}]."),
        };

        return ['twilio' => $twilio];
    }

    public function sendPayload(string $to, string $type, array $payload): ProviderResult
    {
        if (isset($payload['twilio']) && is_array($payload['twilio'])) {
            return $this->dispatch($payload['twilio']);
        }

        return match ($type) {
            'text' => $this->sendText($to, (string) ($payload['text']['body'] ?? '')),
            'template' => $this->sendTemplate(
                $to,
                (string) ($payload['template']['name'] ?? ''),
                (string) ($payload['template']['language']['code'] ?? 'en_US'),
                $payload['template']['components'] ?? [],
            ),
            'image', 'document', 'video', 'audio' => $this->sendMedia(
                $to,
                (string) ($payload[$type]['link'] ?? ''),
                $payload[$type]['caption'] ?? null,
            ),
            'location' => $this->sendLocation(
                $to,
                (float) ($payload['location']['latitude'] ?? 0),
                (float) ($payload['location']['longitude'] ?? 0),
                $payload['location']['name'] ?? null,
                $payload['location']['address'] ?? null,
            ),
            'buttons' => $this->sendButtons(
                $to,
                (string) ($payload['body'] ?? ''),
                $payload['buttons'] ?? [],
                $payload['header'] ?? null,
                $payload['footer'] ?? null,
            ),
            'list' => $this->sendList(
                $to,
                (string) ($payload['body'] ?? ''),
                (string) ($payload['button_text'] ?? 'Options'),
                $payload['sections'] ?? [],
                $payload['header'] ?? null,
                $payload['footer'] ?? null,
            ),
            default => throw new WhatsAppException("Unsupported Twilio payload type [{$type}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function sendMedia(string $to, string $link, ?string $caption = null): ProviderResult
    {
        WhatsAppMessageBuilder::assertValidMediaUrl($link);

        $payload = [
            'From' => $this->fromAddress(),
            'To' => $this->toAddress($to),
            'MediaUrl' => $link,
        ];

        if ($caption !== null) {
            $payload['Body'] = $caption;
        }

        return $this->dispatch($payload);
    }

    public function sendButtons(
        string $to,
        string $body,
        array $buttons,
        ?string $header = null,
        ?string $footer = null,
    ): ProviderResult {
        $lines = array_map(fn (array $b) => ($b['title'] ?? 'Option'), $buttons);

        return $this->sendText($to, trim(($header ? $header."\n\n" : '').$body."\n\n".implode("\n", $lines)));
    }

    public function sendList(
        string $to,
        string $body,
        string $buttonText,
        array $sections,
        ?string $header = null,
        ?string $footer = null,
    ): ProviderResult {
        $options = [];

        foreach ($sections as $section) {
            foreach ($section['rows'] ?? [] as $row) {
                $options[] = ($row['title'] ?? '').': '.($row['description'] ?? '');
            }
        }

        return $this->sendText($to, trim(($header ? $header."\n\n" : '').$body."\n\n".implode("\n", $options)));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function dispatch(array $payload): ProviderResult
    {
        $this->ensureConfigured();

        $url = sprintf(
            'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json',
            $this->account->twilio_sid,
        );

        $response = Http::withBasicAuth(
            (string) $this->account->twilio_sid,
            (string) $this->account->twilio_token,
        )
            ->asForm()
            ->acceptJson()
            ->timeout(30)
            ->post($url, $payload);

        $data = $response->json() ?? [];

        if ($response->failed()) {
            $message = $data['message'] ?? $response->body();

            throw new WhatsAppException(
                "Twilio API request failed: {$message}",
                $data,
                $response->status(),
            );
        }

        return new ProviderResult(['twilio' => $payload], $data);
    }

    protected function fromAddress(): string
    {
        $number = $this->account->twilio_whatsapp_number ?: $this->account->phone_number;

        return 'whatsapp:+'.WhatsAppMessageBuilder::normalizePhone((string) $number);
    }

    protected function toAddress(string $to): string
    {
        return 'whatsapp:+'.WhatsAppMessageBuilder::normalizePhone($to);
    }

    protected function ensureConfigured(): void
    {
        if (! $this->account->twilio_sid || ! $this->account->twilio_token) {
            throw new WhatsAppException('Twilio provider requires twilio_sid and twilio_token.');
        }
    }
}
