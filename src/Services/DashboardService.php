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
    public function stats(): array
    {
        $today = now()->startOfDay();

        return [
            'total_contacts' => WhatsAppContact::query()->count(),
            'total_conversations' => WhatsAppConversation::query()->count(),
            'incoming_today' => WhatsAppConversationMessage::query()
                ->where('direction', WhatsAppConversationMessage::DIRECTION_INCOMING)
                ->where('created_at', '>=', $today)
                ->count(),
            'outgoing_today' => WhatsAppConversationMessage::query()
                ->where('direction', WhatsAppConversationMessage::DIRECTION_OUTGOING)
                ->where('created_at', '>=', $today)
                ->count(),
        ];
    }
}
