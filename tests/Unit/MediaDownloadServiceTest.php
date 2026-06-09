<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Events\MediaDownloaded;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMediaFile;
use Vendor\LaravelWhatsAppCloud\Services\MediaDownloadService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class MediaDownloadServiceTest extends TestCase
{
    protected function metaAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'media',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'pid',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_downloads_and_stores_incoming_media(): void
    {
        Event::fake([MediaDownloaded::class]);
        Storage::fake('local');
        config(['whatsapp.media.enabled' => true]);

        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push(['url' => 'https://lookaside.fbsbx.com/audio', 'mime_type' => 'audio/ogg'])
                ->push('binary-audio-data'),
            'lookaside.fbsbx.com/*' => Http::response('binary-audio-data'),
        ]);

        $account = $this->metaAccount();
        $message = [
            'from' => '923009999999',
            'id' => 'wamid.audio',
            'type' => 'audio',
            'audio' => ['id' => 'media-123'],
        ];

        $file = app(MediaDownloadService::class)->downloadFromIncomingMessage($account, $message);

        $this->assertInstanceOf(WhatsAppMediaFile::class, $file);
        $this->assertSame('media-123', $file->media_id);
        Storage::disk('local')->assertExists($file->path);

        Event::assertDispatched(MediaDownloaded::class, function (MediaDownloaded $event) use ($account) {
            return $event->account->id === $account->id
                && $event->file->media_id === 'media-123';
        });
    }

    #[Test]
    public function it_returns_existing_media_without_redownloading(): void
    {
        Event::fake([MediaDownloaded::class]);
        config(['whatsapp.media.enabled' => true]);

        $account = $this->metaAccount();
        $existing = WhatsAppMediaFile::query()->create([
            'account_id' => $account->id,
            'media_id' => 'cached-id',
            'mime_type' => 'image/jpeg',
            'path' => 'whatsapp/media/1/cached-id',
        ]);

        $file = app(MediaDownloadService::class)->downloadFromIncomingMessage($account, [
            'type' => 'image',
            'image' => ['id' => 'cached-id'],
        ]);

        $this->assertSame($existing->id, $file->id);
        Event::assertNotDispatched(MediaDownloaded::class);
    }

    #[Test]
    public function it_skips_download_when_disabled_or_non_meta(): void
    {
        config(['whatsapp.media.enabled' => false]);

        $account = $this->metaAccount();

        $this->assertNull(app(MediaDownloadService::class)->downloadFromIncomingMessage($account, [
            'type' => 'image',
            'image' => ['id' => 'x'],
        ]));

        $twilio = WhatsAppAccount::query()->create([
            'name' => 'twilio',
            'provider' => 'twilio',
            'phone_number' => '923001234567',
            'twilio_sid' => 'AC1',
            'twilio_token' => 'token-1234567890',
            'twilio_whatsapp_number' => '14155238886',
            'is_default' => false,
            'is_active' => true,
        ]);

        config(['whatsapp.media.enabled' => true]);

        $this->assertNull(app(MediaDownloadService::class)->downloadFromIncomingMessage($twilio, [
            'type' => 'image',
            'image' => ['id' => 'x'],
        ]));
    }
}
