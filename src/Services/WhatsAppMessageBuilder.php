<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;

class WhatsAppMessageBuilder
{
    public static function text(string $to, string $text, bool $previewUrl = false): array
    {
        self::assertNonEmpty($text, 'Text message body cannot be empty.');

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizePhone($to),
            'type' => 'text',
            'text' => [
                'preview_url' => $previewUrl,
                'body' => $text,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $components
     */
    /**
     * @param  array<int, string|int|float>  $variables
     * @return array<int, array<string, mixed>>
     */
    public static function templateComponentsFromVariables(array $variables): array
    {
        if ($variables === []) {
            return [];
        }

        return [
            [
                'type' => 'body',
                'parameters' => array_map(
                    fn (string|int|float $value) => [
                        'type' => 'text',
                        'text' => (string) $value,
                    ],
                    array_values($variables),
                ),
            ],
        ];
    }

    public static function template(
        string $to,
        string $name,
        string $language = 'en_US',
        array $components = [],
    ): array {
        self::assertNonEmpty($name, 'Template name cannot be empty.');

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizePhone($to),
            'type' => 'template',
            'template' => [
                'name' => $name,
                'language' => ['code' => $language],
            ],
        ];

        if ($components !== []) {
            $payload['template']['components'] = $components;
        }

        return $payload;
    }

    public static function image(string $to, string $link, ?string $caption = null): array
    {
        self::assertValidMediaUrl($link);

        $image = ['link' => $link];

        if ($caption !== null) {
            $image['caption'] = $caption;
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizePhone($to),
            'type' => 'image',
            'image' => $image,
        ];
    }

    public static function imageFromId(string $to, string $mediaId, ?string $caption = null): array
    {
        self::assertNonEmpty($mediaId, 'Media ID cannot be empty.');

        $image = ['id' => $mediaId];

        if ($caption !== null) {
            $image['caption'] = $caption;
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizePhone($to),
            'type' => 'image',
            'image' => $image,
        ];
    }

    public static function document(
        string $to,
        string $link,
        ?string $filename = null,
        ?string $caption = null,
    ): array {
        self::assertValidMediaUrl($link);

        $document = ['link' => $link];

        if ($filename !== null) {
            $document['filename'] = $filename;
        }

        if ($caption !== null) {
            $document['caption'] = $caption;
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizePhone($to),
            'type' => 'document',
            'document' => $document,
        ];
    }

    public static function documentFromId(
        string $to,
        string $mediaId,
        ?string $filename = null,
        ?string $caption = null,
    ): array {
        self::assertNonEmpty($mediaId, 'Media ID cannot be empty.');

        $document = ['id' => $mediaId];

        if ($filename !== null) {
            $document['filename'] = $filename;
        }

        if ($caption !== null) {
            $document['caption'] = $caption;
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizePhone($to),
            'type' => 'document',
            'document' => $document,
        ];
    }

    public static function audio(string $to, string $link): array
    {
        self::assertValidMediaUrl($link);

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizePhone($to),
            'type' => 'audio',
            'audio' => ['link' => $link],
        ];
    }

    public static function video(string $to, string $link, ?string $caption = null): array
    {
        self::assertValidMediaUrl($link);

        $video = ['link' => $link];

        if ($caption !== null) {
            $video['caption'] = $caption;
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizePhone($to),
            'type' => 'video',
            'video' => $video,
        ];
    }

    public static function location(
        string $to,
        float $latitude,
        float $longitude,
        ?string $name = null,
        ?string $address = null,
    ): array {
        if ($latitude < -90 || $latitude > 90) {
            throw new WhatsAppException('Latitude must be between -90 and 90.');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new WhatsAppException('Longitude must be between -180 and 180.');
        }

        $location = [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        if ($name !== null) {
            $location['name'] = $name;
        }

        if ($address !== null) {
            $location['address'] = $address;
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizePhone($to),
            'type' => 'location',
            'location' => $location,
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $buttons
     */
    public static function interactiveButtons(
        string $to,
        string $body,
        array $buttons,
        ?string $header = null,
        ?string $footer = null,
    ): array {
        self::assertNonEmpty($body, 'Interactive message body cannot be empty.');

        if ($buttons === []) {
            throw new WhatsAppException('At least one button is required.');
        }

        $actionButtons = array_map(
            fn (array $button) => [
                'type' => 'reply',
                'reply' => [
                    'id' => (string) ($button['id'] ?? $button['title']),
                    'title' => (string) ($button['title'] ?? 'Button'),
                ],
            ],
            array_slice($buttons, 0, 3),
        );

        $interactive = [
            'type' => 'button',
            'body' => ['text' => $body],
            'action' => ['buttons' => $actionButtons],
        ];

        if ($header !== null) {
            $interactive['header'] = ['type' => 'text', 'text' => $header];
        }

        if ($footer !== null) {
            $interactive['footer'] = ['text' => $footer];
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizePhone($to),
            'type' => 'interactive',
            'interactive' => $interactive,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    public static function interactiveList(
        string $to,
        string $body,
        string $buttonText,
        array $sections,
        ?string $header = null,
        ?string $footer = null,
    ): array {
        self::assertNonEmpty($body, 'List message body cannot be empty.');
        self::assertNonEmpty($buttonText, 'List button text cannot be empty.');

        $interactive = [
            'type' => 'list',
            'body' => ['text' => $body],
            'action' => [
                'button' => $buttonText,
                'sections' => $sections,
            ],
        ];

        if ($header !== null) {
            $interactive['header'] = ['type' => 'text', 'text' => $header];
        }

        if ($footer !== null) {
            $interactive['footer'] = ['text' => $footer];
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizePhone($to),
            'type' => 'interactive',
            'interactive' => $interactive,
        ];
    }

    public static function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if ($normalized === '') {
            throw new WhatsAppException('Recipient phone number is invalid.');
        }

        return $normalized;
    }

    protected static function assertNonEmpty(string $value, string $message): void
    {
        if (trim($value) === '') {
            throw new WhatsAppException($message);
        }
    }

    public static function assertValidMediaUrl(string $url): void
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new WhatsAppException('Media URL must be a valid absolute URL.');
        }
    }
}
