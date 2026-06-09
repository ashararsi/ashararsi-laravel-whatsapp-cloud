<?php

namespace Vendor\LaravelWhatsAppCloud\Providers;

use Vendor\LaravelWhatsAppCloud\Contracts\SupportsInteractiveMessages;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppProviderInterface;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Services\MediaUploadService;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppMessageBuilder;
use Vendor\LaravelWhatsAppCloud\Support\ProviderResult;

class MetaProvider implements SupportsInteractiveMessages, WhatsAppProviderInterface
{
    public function __construct(
        protected WhatsAppAccount $account,
        protected WhatsAppClientInterface $client,
        protected MediaUploadService $mediaUpload,
    ) {}

    public function sendText(string $to, string $text, bool $previewUrl = false): ProviderResult
    {
        return $this->sendPayload(
            $to,
            'text',
            WhatsAppMessageBuilder::text($to, $text, $previewUrl),
        );
    }

    public function sendTemplate(
        string $to,
        string $name,
        string $language = 'en_US',
        array $components = [],
    ): ProviderResult {
        return $this->sendPayload(
            $to,
            'template',
            WhatsAppMessageBuilder::template($to, $name, $language, $components),
        );
    }

    public function sendImage(string $to, string $link, ?string $caption = null): ProviderResult
    {
        return $this->sendPayload(
            $to,
            'image',
            WhatsAppMessageBuilder::image($to, $link, $caption),
        );
    }

    public function sendDocument(
        string $to,
        string $link,
        ?string $filename = null,
        ?string $caption = null,
    ): ProviderResult {
        return $this->sendPayload(
            $to,
            'document',
            WhatsAppMessageBuilder::document($to, $link, $filename, $caption),
        );
    }

    public function sendVideo(string $to, string $link, ?string $caption = null): ProviderResult
    {
        return $this->sendPayload(
            $to,
            'video',
            WhatsAppMessageBuilder::video($to, $link, $caption),
        );
    }

    public function sendAudio(string $to, string $link): ProviderResult
    {
        return $this->sendPayload(
            $to,
            'audio',
            WhatsAppMessageBuilder::audio($to, $link),
        );
    }

    public function sendImageFile(string $to, string $filePath, ?string $caption = null): ProviderResult
    {
        $mediaId = $this->mediaUpload->upload($this->account, $filePath);

        return $this->sendPayload(
            $to,
            'image',
            WhatsAppMessageBuilder::imageFromId($to, $mediaId, $caption),
        );
    }

    public function sendDocumentFile(
        string $to,
        string $filePath,
        ?string $filename = null,
        ?string $caption = null,
    ): ProviderResult {
        $mediaId = $this->mediaUpload->upload($this->account, $filePath);
        $filename ??= basename($filePath);

        return $this->sendPayload(
            $to,
            'document',
            WhatsAppMessageBuilder::documentFromId($to, $mediaId, $filename, $caption),
        );
    }

    public function sendFile(string $to, string $filePath, ?string $caption = null): ProviderResult
    {
        $mime = is_file($filePath) ? (mime_content_type($filePath) ?: '') : '';
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $isImage = str_starts_with($mime, 'image/')
            || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);

        return $isImage
            ? $this->sendImageFile($to, $filePath, $caption)
            : $this->sendDocumentFile($to, $filePath, basename($filePath), $caption);
    }

    public function sendLocation(
        string $to,
        float $latitude,
        float $longitude,
        ?string $name = null,
        ?string $address = null,
    ): ProviderResult {
        return $this->sendPayload(
            $to,
            'location',
            WhatsAppMessageBuilder::location($to, $latitude, $longitude, $name, $address),
        );
    }

    public function sendButtons(
        string $to,
        string $body,
        array $buttons,
        ?string $header = null,
        ?string $footer = null,
    ): ProviderResult {
        return $this->sendPayload(
            $to,
            'interactive',
            WhatsAppMessageBuilder::interactiveButtons($to, $body, $buttons, $header, $footer),
        );
    }

    public function sendList(
        string $to,
        string $body,
        string $buttonText,
        array $sections,
        ?string $header = null,
        ?string $footer = null,
    ): ProviderResult {
        return $this->sendPayload(
            $to,
            'interactive',
            WhatsAppMessageBuilder::interactiveList($to, $body, $buttonText, $sections, $header, $footer),
        );
    }

    public function buildPayload(string $type, string $to, array $options = []): array
    {
        return match ($type) {
            'text' => WhatsAppMessageBuilder::text(
                $to,
                (string) ($options['text'] ?? ''),
                (bool) ($options['preview_url'] ?? false),
            ),
            'template' => WhatsAppMessageBuilder::template(
                $to,
                (string) ($options['name'] ?? ''),
                (string) ($options['language'] ?? 'en_US'),
                $options['components'] ?? [],
            ),
            'image' => WhatsAppMessageBuilder::image($to, (string) ($options['link'] ?? ''), $options['caption'] ?? null),
            'document' => WhatsAppMessageBuilder::document(
                $to,
                (string) ($options['link'] ?? ''),
                $options['filename'] ?? null,
                $options['caption'] ?? null,
            ),
            'video' => WhatsAppMessageBuilder::video($to, (string) ($options['link'] ?? ''), $options['caption'] ?? null),
            'audio' => WhatsAppMessageBuilder::audio($to, (string) ($options['link'] ?? '')),
            'image_file' => WhatsAppMessageBuilder::imageFromId(
                $to,
                (string) ($options['media_id'] ?? ''),
                $options['caption'] ?? null,
            ),
            'document_file' => WhatsAppMessageBuilder::documentFromId(
                $to,
                (string) ($options['media_id'] ?? ''),
                $options['filename'] ?? null,
                $options['caption'] ?? null,
            ),
            'location' => WhatsAppMessageBuilder::location(
                $to,
                (float) ($options['latitude'] ?? 0),
                (float) ($options['longitude'] ?? 0),
                $options['name'] ?? null,
                $options['address'] ?? null,
            ),
            'buttons' => WhatsAppMessageBuilder::interactiveButtons(
                $to,
                (string) ($options['body'] ?? ''),
                $options['buttons'] ?? [],
                $options['header'] ?? null,
                $options['footer'] ?? null,
            ),
            'list' => WhatsAppMessageBuilder::interactiveList(
                $to,
                (string) ($options['body'] ?? ''),
                (string) ($options['button_text'] ?? 'Options'),
                $options['sections'] ?? [],
                $options['header'] ?? null,
                $options['footer'] ?? null,
            ),
            default => throw new WhatsAppException("Unsupported Meta payload type [{$type}]."),
        };
    }

    public function sendPayload(string $to, string $type, array $payload): ProviderResult
    {
        $this->ensureConfigured();

        $response = $this->client->send(
            (string) $this->account->phone_number_id,
            (string) $this->account->access_token,
            $payload,
        );

        return new ProviderResult($payload, $response);
    }

    protected function ensureConfigured(): void
    {
        if (! $this->account->phone_number_id || ! $this->account->access_token) {
            throw new WhatsAppException('Meta provider requires phone_number_id and access_token.');
        }
    }
}
