<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Events\TranscriptionCompleted;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMediaFile;
use Vendor\LaravelWhatsAppCloud\Services\AudioTranscriptionService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class AudioTranscriptionServiceTest extends TestCase
{
    #[Test]
    public function it_transcribes_audio_and_dispatches_event(): void
    {
        Event::fake([TranscriptionCompleted::class]);
        Storage::fake('local');
        Storage::disk('local')->put('whatsapp/media/1/audio.ogg', 'audio-bytes');

        config([
            'whatsapp.ai.transcription_enabled' => true,
            'whatsapp.openai.api_key' => 'sk-test',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response(['text' => 'Hello from audio']),
        ]);

        $account = WhatsAppAccount::query()->create([
            'name' => 'transcribe',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'pid',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $file = WhatsAppMediaFile::query()->create([
            'account_id' => $account->id,
            'media_id' => 'audio-1',
            'mime_type' => 'audio/ogg',
            'disk' => 'local',
            'path' => 'whatsapp/media/1/audio.ogg',
        ]);

        $text = app(AudioTranscriptionService::class)->transcribeMediaFile($file);

        $this->assertSame('Hello from audio', $text);
        $this->assertSame('Hello from audio', $file->fresh()->transcription);

        Event::assertDispatched(TranscriptionCompleted::class, function (TranscriptionCompleted $event) {
            return $event->text === 'Hello from audio';
        });
    }

    #[Test]
    public function it_returns_null_when_disabled_or_not_audio(): void
    {
        Event::fake([TranscriptionCompleted::class]);
        config(['whatsapp.ai.transcription_enabled' => false]);

        $file = WhatsAppMediaFile::query()->create([
            'account_id' => 1,
            'media_id' => 'img-1',
            'mime_type' => 'image/jpeg',
            'path' => 'x',
        ]);

        $this->assertNull(app(AudioTranscriptionService::class)->transcribeMediaFile($file));
        Event::assertNotDispatched(TranscriptionCompleted::class);
    }

    #[Test]
    public function it_handles_openai_failures_gracefully(): void
    {
        Event::fake([TranscriptionCompleted::class]);
        Storage::fake('local');
        Storage::disk('local')->put('whatsapp/media/1/audio.ogg', 'audio-bytes');

        config([
            'whatsapp.ai.transcription_enabled' => true,
            'whatsapp.openai.api_key' => 'sk-test',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'fail'], 500),
        ]);

        $file = WhatsAppMediaFile::query()->create([
            'account_id' => 1,
            'media_id' => 'audio-1',
            'mime_type' => 'audio/ogg',
            'disk' => 'local',
            'path' => 'whatsapp/media/1/audio.ogg',
        ]);

        $this->assertNull(app(AudioTranscriptionService::class)->transcribeMediaFile($file));
        Event::assertNotDispatched(TranscriptionCompleted::class);
    }
}
