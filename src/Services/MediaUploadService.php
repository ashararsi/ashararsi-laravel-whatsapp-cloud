<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

class MediaUploadService
{
    public function __construct(
        protected GraphApiClient $graphApi,
    ) {}

    public function upload(WhatsAppAccount $account, string $filePath): string
    {
        $this->ensureMetaConfigured($account);

        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new WhatsAppException("Media file not found or not readable: {$filePath}");
        }

        $mimeType = $this->detectMimeType($filePath);

        $response = $this->graphApi->uploadMedia(
            (string) $account->access_token,
            (string) $account->phone_number_id,
            $filePath,
            $mimeType,
        );

        $mediaId = $response['id'] ?? null;

        if (! is_string($mediaId) || $mediaId === '') {
            throw new WhatsAppException('Meta media upload did not return a media ID.', $response);
        }

        return $mediaId;
    }

    protected function detectMimeType(string $filePath): string
    {
        $mime = mime_content_type($filePath);

        if (is_string($mime) && $mime !== '') {
            return $mime;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }

    protected function ensureMetaConfigured(WhatsAppAccount $account): void
    {
        if (! $account->isMeta()) {
            throw new WhatsAppException('Media upload is only supported for Meta accounts.');
        }

        if (! $account->phone_number_id || ! $account->access_token) {
            throw new WhatsAppException('Meta account requires phone_number_id and access_token for media upload.');
        }
    }
}
