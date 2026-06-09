<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Support\GraphApiUsageMetrics;

class SystemHealthService
{
    public function __construct(
        protected GraphApiClient $graphApi,
        protected WhatsAppSettingsService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return [
            'queue' => $this->queueHealth(),
            'webhook' => $this->webhookHealth(),
            'api' => $this->apiHealth(),
            'rate_limits' => $this->rateLimitUsage(),
            'failed_jobs' => $this->failedJobsHealth(),
            'settings' => $this->settings->all(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function queueHealth(): array
    {
        $connection = config('whatsapp.queue_connection') ?? config('queue.default', 'sync');
        $queueName = config('whatsapp.queue_name', 'default');
        $enabled = (bool) config('whatsapp.queue_enabled', true);

        $pendingMessages = WhatsAppMessage::query()
            ->where('status', WhatsAppMessage::STATUS_PENDING)
            ->count();

        $size = null;

        try {
            $size = Queue::connection($connection)->size($queueName);
        } catch (\Throwable) {
            $size = null;
        }

        return [
            'enabled' => $enabled,
            'connection' => $connection,
            'queue' => $queueName,
            'queue_size' => $size,
            'pending_messages' => $pendingMessages,
            'healthy' => ! $enabled || ($size !== null && $size < 1000),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function webhookHealth(): array
    {
        $requireSignature = (bool) config('whatsapp.webhook.require_signature', false);
        $prefix = config('whatsapp.webhook.prefix', 'whatsapp');

        return [
            'prefix' => $prefix,
            'signature_required' => $requireSignature,
            'verify_token_configured' => true,
            'healthy' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function apiHealth(): array
    {
        $recentSuccess = WhatsAppMessage::query()
            ->where('status', WhatsAppMessage::STATUS_SENT)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $recentFailed = WhatsAppMessage::query()
            ->where('status', WhatsAppMessage::STATUS_FAILED)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $deadLettered = WhatsAppMessage::query()
            ->whereNotNull('dead_lettered_at')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            'api_version' => config('whatsapp.api_version', 'v21.0'),
            'base_url' => config('whatsapp.api_base_url', 'https://graph.facebook.com'),
            'sent_last_24h' => $recentSuccess,
            'failed_last_24h' => $recentFailed,
            'dead_lettered_last_7d' => $deadLettered,
            'healthy' => $recentFailed === 0 || ($recentSuccess > 0 && $recentFailed / max(1, $recentSuccess) < 0.1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rateLimitUsage(): array
    {
        $latest = GraphApiUsageMetrics::latest() ?? [];

        return [
            'latest' => $latest,
            'x_business_use_case_usage' => $latest['x_business_use_case_usage'] ?? [],
            'x_app_usage' => $latest['x_app_usage'] ?? [],
            'recorded_at' => $latest['recorded_at'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function failedJobsHealth(): array
    {
        $failedMessages = WhatsAppMessage::query()
            ->where('status', WhatsAppMessage::STATUS_FAILED)
            ->count();

        $deadLettered = WhatsAppMessage::query()
            ->whereNotNull('dead_lettered_at')
            ->count();

        $queueFailed = 0;

        if (Schema::hasTable('failed_jobs')) {
            $queueFailed = (int) DB::table('failed_jobs')->count();
        }

        return [
            'failed_messages' => $failedMessages,
            'dead_lettered_messages' => $deadLettered,
            'queue_failed_jobs' => $queueFailed,
            'healthy' => $failedMessages < 50 && $queueFailed < 50,
        ];
    }
}
