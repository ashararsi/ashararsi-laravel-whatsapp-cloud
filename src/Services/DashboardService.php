<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversation;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversationMessage;

class DashboardService
{
    /**
     * @return array{
     *     total_contacts: int,
     *     total_conversations: int,
     *     incoming_today: int,
     *     outgoing_today: int
     * }
     */
    public function stats(?int $accountId = null): array
    {
        $today = now()->startOfDay();

        return [
            'total_contacts' => WhatsAppContact::query()
                ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
                ->count(),
            'total_conversations' => WhatsAppConversation::query()
                ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
                ->count(),
            'incoming_today' => WhatsAppConversationMessage::query()
                ->where('direction', WhatsAppConversationMessage::DIRECTION_INCOMING)
                ->where('created_at', '>=', $today)
                ->when($accountId, fn ($q) => $q->whereHas('conversation', fn ($c) => $c->where('account_id', $accountId)))
                ->count(),
            'outgoing_today' => WhatsAppConversationMessage::query()
                ->where('direction', WhatsAppConversationMessage::DIRECTION_OUTGOING)
                ->where('created_at', '>=', $today)
                ->when($accountId, fn ($q) => $q->whereHas('conversation', fn ($c) => $c->where('account_id', $accountId)))
                ->count(),
        ];
    }
}
