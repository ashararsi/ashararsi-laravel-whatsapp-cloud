<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Vendor\LaravelWhatsAppCloud\Events\AiReplyGenerated;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

class AiAutoReplyEngine
{
    public function __construct(
        protected OpenAiService $openAi,
    ) {}

    public function reply(WhatsAppAccount $account, string $phone, string $incomingMessage): ?string
    {
        if (! config('whatsapp.ai.enabled', false) || ! $this->openAi->isConfigured()) {
            return null;
        }

        $system = (string) config('whatsapp.ai.system_prompt', 'You are a helpful WhatsApp assistant. Reply concisely.');

        try {
            $reply = trim($this->openAi->chat($system, $incomingMessage));
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        if ($reply === '') {
            return null;
        }

        $sender = WhatsApp::account($account->id);

        if (config('whatsapp.ai.use_queue', false) && config('whatsapp.queue_enabled', true)) {
            $sender = $sender->queue();
        }

        $sender->sendText($phone, $reply);

        event(new AiReplyGenerated($account, $phone, $incomingMessage, $reply));

        return $reply;
    }
}
