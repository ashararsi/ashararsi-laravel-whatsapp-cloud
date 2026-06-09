<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Vendor\LaravelWhatsAppCloud\Events\MediaDownloaded;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversationMessage;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMediaFile;

class MediaDownloadService
{
    public function downloadFromIncomingMessage(
        WhatsAppAccount $account,
        array $webhookMessage,
        ?WhatsAppConversationMessage $conversationMessage = null,
    ): ?WhatsAppMediaFile {
        if (! config('whatsapp.media.enabled', true) || ! $account->isMeta()) {
            return null;
        }

        $mediaId = $this->extractMediaId($webhookMessage);

        if (! $mediaId) {
            return null;
        }

        $existing = WhatsAppMediaFile::query()
            ->where('account_id', $account->id)
            ->where('media_id', $mediaId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $meta = $this->fetchMediaMetadata($account, $mediaId);
        $binary = $this->downloadMediaBinary($account, (string) ($meta['url'] ?? ''));

        $disk = (string) config('whatsapp.media.disk', 'local');
        $path = 'whatsapp/media/'.$account->id.'/'.$mediaId;
        Storage::disk($disk)->put($path, $binary);

        $file = WhatsAppMediaFile::query()->create([
            'account_id' => $account->id,
            'conversation_message_id' => $conversationMessage?->id,
            'media_id' => $mediaId,
            'mime_type' => $meta['mime_type'] ?? null,
            'disk' => $disk,
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'size' => strlen($binary),
        ]);

        event(new MediaDownloaded($account, $file, $webhookMessage));

        return $file;
    }

    protected function extractMediaId(array $message): ?string
    {
        $type = $message['type'] ?? null;

        if (! is_string($type)) {
            return null;
        }

        $id = $message[$type]['id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function fetchMediaMetadata(WhatsAppAccount $account, string $mediaId): array
    {
        $version = config('whatsapp.api_version', 'v21.0');
        $base = rtrim((string) config('whatsapp.api_base_url', 'https://graph.facebook.com'), '/');

        $response = Http::withToken((string) $account->access_token)
            ->get("{$base}/{$version}/{$mediaId}");

        if (! $response->successful()) {
            throw new WhatsAppException('Failed to fetch media metadata.', $response->json() ?? []);
        }

        return $response->json() ?? [];
    }

    protected function downloadMediaBinary(WhatsAppAccount $account, string $url): string
    {
        if ($url === '') {
            throw new WhatsAppException('Media URL missing from Meta response.');
        }

        $response = Http::withToken((string) $account->access_token)->get($url);

        if (! $response->successful()) {
            throw new WhatsAppException('Failed to download media binary.');
        }

        return $response->body();
    }
}
