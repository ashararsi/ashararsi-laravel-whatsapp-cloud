<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Support\Facades\Http;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;

class OpenAiService
{
    public function isConfigured(): bool
    {
        return (string) config('whatsapp.openai.api_key', '') !== '';
    }

    public function chat(string $systemPrompt, string $userMessage, ?string $model = null): string
    {
        $this->ensureConfigured();

        $response = Http::withToken((string) config('whatsapp.openai.api_key'))
            ->timeout((int) config('whatsapp.openai.timeout', 30))
            ->post(rtrim((string) config('whatsapp.openai.base_url', 'https://api.openai.com/v1'), '/').'/chat/completions', [
                'model' => $model ?? config('whatsapp.openai.chat_model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => (float) config('whatsapp.openai.temperature', 0.7),
            ]);

        if (! $response->successful()) {
            throw new WhatsAppException('OpenAI chat request failed.', $response->json() ?? []);
        }

        return (string) ($response->json('choices.0.message.content') ?? '');
    }

    public function transcribeAudio(string $absolutePath, ?string $model = null): string
    {
        $this->ensureConfigured();

        $response = Http::withToken((string) config('whatsapp.openai.api_key'))
            ->timeout((int) config('whatsapp.openai.timeout', 60))
            ->attach('file', fopen($absolutePath, 'r'), basename($absolutePath))
            ->post(rtrim((string) config('whatsapp.openai.base_url', 'https://api.openai.com/v1'), '/').'/audio/transcriptions', [
                'model' => $model ?? config('whatsapp.openai.whisper_model', 'whisper-1'),
            ]);

        if (! $response->successful()) {
            throw new WhatsAppException('OpenAI transcription failed.', $response->json() ?? []);
        }

        return (string) ($response->json('text') ?? '');
    }

    protected function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new WhatsAppException('OpenAI API key is not configured.');
        }
    }
}
