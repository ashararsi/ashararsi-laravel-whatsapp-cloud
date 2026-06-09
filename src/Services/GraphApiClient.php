<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Support\GraphApiUsageMetrics;

class GraphApiClient
{
    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $accessToken, string $path, array $query = []): array
    {
        return $this->request('GET', $accessToken, $path, query: $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $accessToken, string $path, array $payload = []): array
    {
        return $this->request('POST', $accessToken, $path, payload: $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function uploadMedia(
        string $accessToken,
        string $phoneNumberId,
        string $filePath,
        string $mimeType,
    ): array {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new WhatsAppException("Media file not found or not readable: {$filePath}");
        }

        $path = "{$phoneNumberId}/media";
        $attempt = 0;
        $maxRetries = $this->maxRetries();

        while (true) {
            $attempt++;

            $response = $this->http($accessToken)
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post($this->url($path), [
                    'messaging_product' => 'whatsapp',
                    'type' => $mimeType,
                ]);

            $this->recordUsage($response, $path);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            if ($this->shouldRetry($response->status(), $attempt, $maxRetries)) {
                $this->sleepBeforeRetry($attempt, $response->header('Retry-After'));

                continue;
            }

            $this->throwFromResponse($response);
        }
    }

    public function getBinary(string $accessToken, string $url): string
    {
        $attempt = 0;
        $maxRetries = $this->maxRetries();

        while (true) {
            $attempt++;

            $response = $this->http($accessToken)->get($url);

            $this->recordUsage($response, $url);

            if ($response->successful()) {
                return $response->body();
            }

            if ($this->shouldRetry($response->status(), $attempt, $maxRetries)) {
                $this->sleepBeforeRetry($attempt, $response->header('Retry-After'));

                continue;
            }

            throw new WhatsAppException(
                'Failed to download binary from Meta.',
                $response->json() ?? [],
                $response->status(),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function request(
        string $method,
        string $accessToken,
        string $path,
        array $query = [],
        array $payload = [],
    ): array {
        $attempt = 0;
        $maxRetries = $this->maxRetries();

        while (true) {
            $attempt++;

            $pending = $this->http($accessToken);
            $url = $this->url($path);

            $response = match (strtoupper($method)) {
                'GET' => $pending->get($url, $query),
                'POST' => $pending->post($url, $payload),
                default => throw new WhatsAppException("Unsupported HTTP method [{$method}]."),
            };

            $this->recordUsage($response, $path);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            if ($this->shouldRetry($response->status(), $attempt, $maxRetries)) {
                Log::warning('WhatsApp Graph API retry', [
                    'method' => $method,
                    'path' => $path,
                    'status' => $response->status(),
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                ]);

                $this->sleepBeforeRetry($attempt, $response->header('Retry-After'));

                continue;
            }

            $this->throwFromResponse($response);
        }
    }

    protected function http(string $accessToken): PendingRequest
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('whatsapp.graph_api.timeout', 30));
    }

    protected function url(string $path): string
    {
        $version = config('whatsapp.api_version', 'v21.0');
        $baseUrl = rtrim((string) config('whatsapp.api_base_url', 'https://graph.facebook.com'), '/');
        $path = ltrim($path, '/');

        return "{$baseUrl}/{$version}/{$path}";
    }

    protected function maxRetries(): int
    {
        return max(0, (int) config('whatsapp.graph_api.max_retries', 3));
    }

    protected function shouldRetry(int $status, int $attempt, int $maxRetries): bool
    {
        if ($attempt > $maxRetries) {
            return false;
        }

        return $status === 429 || $status >= 500;
    }

    protected function sleepBeforeRetry(int $attempt, ?string $retryAfterHeader): void
    {
        $baseMs = (int) config('whatsapp.graph_api.retry_base_delay_ms', 1000);
        $maxMs = (int) config('whatsapp.graph_api.retry_max_delay_ms', 60000);

        if (is_string($retryAfterHeader) && is_numeric($retryAfterHeader)) {
            $delayMs = min($maxMs, (int) $retryAfterHeader * 1000);
        } else {
            $delayMs = min($maxMs, $baseMs * (2 ** ($attempt - 1)));
        }

        usleep($delayMs * 1000);
    }

    protected function recordUsage(Response $response, string $endpoint): void
    {
        GraphApiUsageMetrics::record(
            GraphApiUsageMetrics::parseHeader($response->header('X-Business-Use-Case-Usage')),
            GraphApiUsageMetrics::parseHeader($response->header('X-App-Usage')),
            $response->status(),
            $endpoint,
        );
    }

    protected function throwFromResponse(Response $response): never
    {
        $data = $response->json() ?? [];
        $message = $data['error']['message'] ?? $response->body();

        throw new WhatsAppException(
            "WhatsApp API request failed: {$message}",
            $data,
            $response->status(),
        );
    }
}
