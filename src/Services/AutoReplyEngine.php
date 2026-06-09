<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Vendor\LaravelWhatsAppCloud\Events\AutoReplyTriggered;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAutoReply;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversationMessage;

class AutoReplyEngine
{
    public function __construct(
        protected AiAutoReplyEngine $aiAutoReply,
    ) {}

    public function handleIncoming(
        WhatsAppAccount $account,
        string $phone,
        string $messageBody,
        bool $isFirstMessage = false,
    ): ?string {
        if (! config('whatsapp.auto_reply.enabled', true)) {
            return null;
        }

        $rules = WhatsAppAutoReply::query()
            ->where('account_id', $account->id)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();

        foreach ($rules as $rule) {
            if (! $this->matches($rule, $messageBody, $isFirstMessage)) {
                continue;
            }

            if ($rule->use_ai) {
                return $this->aiAutoReply->reply($account, $phone, $messageBody);
            }

            WhatsApp::account($account->id)->sendText($phone, $rule->response);
            event(new AutoReplyTriggered($account, $rule, $phone, $messageBody));

            return $rule->response;
        }

        if (config('whatsapp.ai.fallback', false)) {
            return $this->aiAutoReply->reply($account, $phone, $messageBody);
        }

        return null;
    }

    protected function matches(WhatsAppAutoReply $rule, string $message, bool $isFirstMessage): bool
    {
        return match ($rule->trigger_type) {
            WhatsAppAutoReply::TRIGGER_FIRST_MESSAGE => $isFirstMessage,
            WhatsAppAutoReply::TRIGGER_ANY => true,
            default => str_contains(
                strtolower($message),
                strtolower($rule->trigger_value),
            ),
        };
    }

    public function isFirstIncomingMessage(WhatsAppAccount $account, string $phone): bool
    {
        return WhatsAppConversationMessage::query()
            ->where('phone', $phone)
            ->where('direction', WhatsAppConversationMessage::DIRECTION_INCOMING)
            ->whereHas('conversation', fn ($q) => $q->where('account_id', $account->id))
            ->count() <= 1;
    }
}
