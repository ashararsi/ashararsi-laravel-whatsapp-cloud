<?php

namespace Vendor\LaravelWhatsAppCloud\Contracts;

use Vendor\LaravelWhatsAppCloud\Support\ProviderResult;

interface WhatsAppProviderInterface
{
    public function sendText(string $to, string $text, bool $previewUrl = false): ProviderResult;

    /**
     * @param  array<string, mixed>  $components
     */
    public function sendTemplate(
        string $to,
        string $name,
        string $language = 'en_US',
        array $components = [],
    ): ProviderResult;

    public function sendImage(string $to, string $link, ?string $caption = null): ProviderResult;

    public function sendDocument(
        string $to,
        string $link,
        ?string $filename = null,
        ?string $caption = null,
    ): ProviderResult;

    public function sendVideo(string $to, string $link, ?string $caption = null): ProviderResult;

    public function sendAudio(string $to, string $link): ProviderResult;

    public function sendImageFile(string $to, string $filePath, ?string $caption = null): ProviderResult;

    public function sendDocumentFile(
        string $to,
        string $filePath,
        ?string $filename = null,
        ?string $caption = null,
    ): ProviderResult;

    public function sendFile(string $to, string $filePath, ?string $caption = null): ProviderResult;

    public function sendLocation(
        string $to,
        float $latitude,
        float $longitude,
        ?string $name = null,
        ?string $address = null,
    ): ProviderResult;

    /**
     * Build an outgoing payload without sending (used for queue persistence).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function buildPayload(string $type, string $to, array $options = []): array;

    /**
     * Replay a queued message using a stored payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function sendPayload(string $to, string $type, array $payload): ProviderResult;
}
