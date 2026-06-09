<?php

namespace Vendor\LaravelWhatsAppCloud\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GraphApiUsageMetrics
{
    public const CACHE_KEY = 'whatsapp.graph_api.usage';

    /**
     * @param  array<string, mixed>  $businessUseCaseUsage
     * @param  array<string, mixed>  $appUsage
     */
    public static function record(
        array $businessUseCaseUsage = [],
        array $appUsage = [],
        ?int $statusCode = null,
        ?string $endpoint = null,
    ): void {
        $payload = [
            'recorded_at' => now()->toIso8601String(),
            'status_code' => $statusCode,
            'endpoint' => $endpoint,
            'x_business_use_case_usage' => $businessUseCaseUsage,
            'x_app_usage' => $appUsage,
        ];

        if (config('whatsapp.cache.enabled', true)) {
            Cache::put(self::CACHE_KEY, $payload, (int) config('whatsapp.cache.ttl', 300));
        }

        Log::info('WhatsApp Graph API usage', $payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function latest(): ?array
    {
        if (! config('whatsapp.cache.enabled', true)) {
            return null;
        }

        $cached = Cache::get(self::CACHE_KEY);

        return is_array($cached) ? $cached : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function parseHeader(?string $header): array
    {
        if (! is_string($header) || trim($header) === '') {
            return [];
        }

        $decoded = json_decode($header, true);

        return is_array($decoded) ? $decoded : [];
    }
}
