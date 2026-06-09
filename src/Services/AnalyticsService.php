<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Support\Facades\DB;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppCampaign;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversation;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversationMessage;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;

class AnalyticsService
{
    public function __construct(
        protected DashboardService $dashboard,
    ) {}

    /**
     * @return array<string, int|array<string, int>>
     */
    public function overview(?int $accountId = null): array
    {
        $stats = $this->dashboard->stats($accountId);

        return array_merge($stats, [
            'open_conversations' => $this->countConversations($accountId, 'open'),
            'closed_conversations' => $this->countConversations($accountId, 'closed'),
            'campaigns_total' => $this->countCampaigns($accountId),
            'messages_sent_total' => $this->countOutgoingLogs($accountId),
            'messages_failed_total' => $this->countOutgoingLogs($accountId, WhatsAppMessage::STATUS_FAILED),
            'daily_messages' => $this->dailyMessageVolume($accountId),
        ]);
    }

    protected function countConversations(?int $accountId, ?string $status = null): int
    {
        return WhatsAppConversation::query()
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->count();
    }

    protected function countCampaigns(?int $accountId): int
    {
        return WhatsAppCampaign::query()
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->count();
    }

    protected function countOutgoingLogs(?int $accountId, ?string $status = null): int
    {
        return WhatsAppMessage::query()
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->count();
    }

    /**
     * @return array<int, array{date: string, incoming: int, outgoing: int}>
     */
    protected function dailyMessageVolume(?int $accountId, int $days = 7): array
    {
        $since = now()->subDays($days - 1)->startOfDay();

        $incoming = WhatsAppConversationMessage::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('direction', WhatsAppConversationMessage::DIRECTION_INCOMING)
            ->where('created_at', '>=', $since)
            ->when($accountId, fn ($q) => $q->whereHas('conversation', fn ($c) => $c->where('account_id', $accountId)))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'date');

        $outgoing = WhatsAppConversationMessage::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('direction', WhatsAppConversationMessage::DIRECTION_OUTGOING)
            ->where('created_at', '>=', $since)
            ->when($accountId, fn ($q) => $q->whereHas('conversation', fn ($c) => $c->where('account_id', $accountId)))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'date');

        $results = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $since->copy()->addDays($i)->toDateString();
            $results[] = [
                'date' => $date,
                'incoming' => (int) ($incoming[$date] ?? 0),
                'outgoing' => (int) ($outgoing[$date] ?? 0),
            ];
        }

        return $results;
    }
}
