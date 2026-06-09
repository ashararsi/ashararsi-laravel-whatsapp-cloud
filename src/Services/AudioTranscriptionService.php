<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Support\Facades\Storage;
use Vendor\LaravelWhatsAppCloud\Events\TranscriptionCompleted;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMediaFile;

class AudioTranscriptionService
{
    public function __construct(
        protected OpenAiService $openAi,
    ) {}

    public function transcribeMediaFile(WhatsAppMediaFile $file): ?string
    {
        if (! config('whatsapp.ai.transcription_enabled', false) || ! $this->openAi->isConfigured()) {
            return null;
        }

        if (! $file->path || ! str_starts_with((string) $file->mime_type, 'audio/')) {
            return null;
        }

        try {
            $absolute = Storage::disk($file->disk)->path($file->path);
            $text = trim($this->openAi->transcribeAudio($absolute));
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        if ($text !== '') {
            $file->update(['transcription' => $text]);
            event(new TranscriptionCompleted($file->fresh(), $text));
        }

        return $text ?: null;
    }
}
