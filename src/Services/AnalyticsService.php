<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Support\Facades\DB;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppCampaign;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversation;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversationMessage;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTemplate;

class AnalyticsService
{
    public function __construct(
        protected DashboardService $dashboard,
    ) {}

    /**
     * @return array<string, int|float|array<string, int|float|array<int, mixed>>>
     */
    public function overview(?int $accountId = null): array
    {
        $stats = $this->dashboard->stats($accountId);
        $messagesToday = $this->messagesToday($accountId);
        $conversationsToday = $this->conversationsToday($accountId);
        $templatesUsed = $this->templatesUsedToday($accountId);
        $deliveryRate = $this->deliveryRate($accountId);
        $estimatedCost = $this->estimatedCostToday($accountId);

        return array_merge($stats, [
            'open_conversations' => $this->countConversations($accountId, 'open'),
            'closed_conversations' => $this->countConversations($accountId, 'closed'),
            'campaigns_total' => $this->countCampaigns($accountId),
            'messages_sent_total' => $this->countOutgoingLogs($accountId),
            'messages_failed_total' => $this->countOutgoingLogs($accountId, WhatsAppMessage::STATUS_FAILED),
            'daily_messages' => $this->dailyMessageVolume($accountId),
            'templates_approved' => $this->countTemplates($accountId, WhatsAppTemplate::STATUS_APPROVED),
            'templates_pending' => $this->countTemplates($accountId, WhatsAppTemplate::STATUS_PENDING),
            'templates_rejected' => $this->countTemplates($accountId, WhatsAppTemplate::STATUS_REJECTED),
            'templates_disabled' => $this->countTemplates($accountId, WhatsAppTemplate::STATUS_DISABLED),
            'templates_total' => $this->countTemplates($accountId),
            'messages_today' => $messagesToday,
            'conversations_today' => $conversationsToday,
            'templates_used_today' => $templatesUsed,
            'delivery_rate' => $deliveryRate,
            'estimated_cost_today' => $estimatedCost,
            'cost_breakdown' => $this->costBreakdownToday($accountId),
            'chart_data' => $this->chartData($accountId),
        ]);
    }

    protected function messagesToday(?int $accountId): int
    {
        return WhatsAppMessage::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->count();
    }

    protected function conversationsToday(?int $accountId): int
    {
        return WhatsAppConversation::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->count();
    }

    protected function templatesUsedToday(?int $accountId): int
    {
        return WhatsAppMessage::query()
            ->where('type', 'template')
            ->where('created_at', '>=', now()->startOfDay())
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->count();
    }

    protected function deliveryRate(?int $accountId): float
    {
        $sent = WhatsAppMessage::query()
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->whereIn('status', [
                WhatsAppMessage::STATUS_SENT,
                WhatsAppMessage::STATUS_DELIVERED,
                WhatsAppMessage::STATUS_READ,
                WhatsAppMessage::STATUS_FAILED,
            ])
            ->count();

        if ($sent === 0) {
            return 100.0;
        }

        $delivered = WhatsAppMessage::query()
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->whereIn('status', [
                WhatsAppMessage::STATUS_DELIVERED,
                WhatsAppMessage::STATUS_READ,
            ])
            ->count();

        $successful = WhatsAppMessage::query()
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->where('status', WhatsAppMessage::STATUS_SENT)
            ->count();

        return round((($delivered + $successful) / $sent) * 100, 2);
    }

    protected function estimatedCostToday(?int $accountId): float
    {
        $breakdown = $this->costBreakdownToday($accountId);

        return round(
            ($breakdown['utility'] ?? 0)
            + ($breakdown['marketing'] ?? 0)
            + ($breakdown['authentication'] ?? 0)
            + ($breakdown['service'] ?? 0),
            4,
        );
    }

    /**
     * @return array<string, float>
     */
    protected function costBreakdownToday(?int $accountId): array
    {
        $rates = config('whatsapp.cost', []);
        $utilityRate = (float) ($rates['utility'] ?? 0.005);
        $marketingRate = (float) ($rates['marketing'] ?? 0.015);
        $authRate = (float) ($rates['authentication'] ?? 0.004);
        $serviceRate = (float) ($rates['service'] ?? 0.0);

        $since = now()->startOfDay();

        $templateMessages = WhatsAppMessage::query()
            ->where('type', 'template')
            ->where('created_at', '>=', $since)
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->get(['meta_json']);

        $utility = 0;
        $marketing = 0;
        $authentication = 0;

        foreach ($templateMessages as $message) {
            $name = $message->meta_json['template']['name'] ?? null;
            $category = $this->resolveTemplateCategory($name, $accountId);

            match (strtoupper((string) $category)) {
                'MARKETING' => $marketing++,
                'AUTHENTICATION' => $authentication++,
                default => $utility++,
            };
        }

        $serviceMessages = WhatsAppMessage::query()
            ->where('type', '!=', 'template')
            ->where('created_at', '>=', $since)
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->count();

        return [
            'utility' => round($utility * $utilityRate, 4),
            'marketing' => round($marketing * $marketingRate, 4),
            'authentication' => round($authentication * $authRate, 4),
            'service' => round($serviceMessages * $serviceRate, 4),
            'utility_count' => $utility,
            'marketing_count' => $marketing,
            'authentication_count' => $authentication,
            'service_count' => $serviceMessages,
        ];
    }

    protected function resolveTemplateCategory(mixed $name, ?int $accountId): ?string
    {
        if (! is_string($name) || $name === '') {
            return null;
        }

        return WhatsAppTemplate::query()
            ->where('template_name', $name)
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->value('category');
    }

    /**
     * @return array<string, mixed>
     */
    protected function chartData(?int $accountId, int $days = 7): array
    {
        $volume = $this->dailyMessageVolume($accountId, $days);

        return [
            'labels' => array_column($volume, 'date'),
            'incoming' => array_column($volume, 'incoming'),
            'outgoing' => array_column($volume, 'outgoing'),
            'templates' => $this->dailyTemplateUsage($accountId, $days),
        ];
    }

    /**
     * @return array<int, int>
     */
    protected function dailyTemplateUsage(?int $accountId, int $days = 7): array
    {
        $since = now()->subDays($days - 1)->startOfDay();

        $counts = WhatsAppMessage::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('type', 'template')
            ->where('created_at', '>=', $since)
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'date');

        $results = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $since->copy()->addDays($i)->toDateString();
            $results[] = (int) ($counts[$date] ?? 0);
        }

        return $results;
    }

    protected function countTemplates(?int $accountId, ?string $status = null): int
    {
        return WhatsAppTemplate::query()
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->count();
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
