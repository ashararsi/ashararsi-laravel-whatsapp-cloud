<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Services\GraphApiClient;
use Vendor\LaravelWhatsAppCloud\Support\GraphApiUsageMetrics;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class GraphApiClientTest extends TestCase
{
    #[Test]
    public function it_posts_to_graph_api(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'ok'], 200),
        ]);

        $result = (new GraphApiClient)->post('token', 'phone-id/messages', ['type' => 'text']);

        $this->assertSame('ok', $result['id']);
    }

    #[Test]
    public function it_gets_from_graph_api(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['data' => []], 200),
        ]);

        $result = (new GraphApiClient)->get('token', 'waba-id/message_templates');

        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function it_retries_on_429_and_succeeds(): void
    {
        config(['whatsapp.graph_api.max_retries' => 2, 'whatsapp.graph_api.retry_base_delay_ms' => 1]);

        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push(['error' => ['message' => 'Rate limit']], 429, ['Retry-After' => '1'])
                ->push(['messages' => [['id' => 'wamid.retry']]], 200),
        ]);

        $result = (new GraphApiClient)->post('token', 'phone-id/messages', []);

        $this->assertSame('wamid.retry', $result['messages'][0]['id']);
        Http::assertSentCount(2);
    }

    #[Test]
    public function it_retries_on_5xx_and_succeeds(): void
    {
        config(['whatsapp.graph_api.max_retries' => 2, 'whatsapp.graph_api.retry_base_delay_ms' => 1]);

        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push(['error' => ['message' => 'Server error']], 503)
                ->push(['id' => 'recovered'], 200),
        ]);

        $result = (new GraphApiClient)->get('token', 'media-id');

        $this->assertSame('recovered', $result['id']);
    }

    #[Test]
    public function it_throws_after_max_retries(): void
    {
        config(['whatsapp.graph_api.max_retries' => 1, 'whatsapp.graph_api.retry_base_delay_ms' => 1]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Rate limit']], 429),
        ]);

        $this->expectException(WhatsAppException::class);

        (new GraphApiClient)->get('token', 'media-id');
    }

    #[Test]
    public function it_logs_rate_limit_usage_headers(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'WhatsApp Graph API usage'
                    && isset($context['x_business_use_case_usage']['whatsapp'])
                    && isset($context['x_app_usage']['call_count']);
            });

        Http::fake([
            'graph.facebook.com/*' => Http::response(['ok' => true], 200, [
                'X-Business-Use-Case-Usage' => json_encode(['whatsapp' => [['type' => 'whatsapp', 'call_count' => 1]]]),
                'X-App-Usage' => json_encode(['call_count' => 1, 'total_cputime' => 1, 'total_time' => 1]),
            ]),
        ]);

        config(['whatsapp.cache.enabled' => true]);

        (new GraphApiClient)->get('token', 'waba-id');

        $latest = GraphApiUsageMetrics::latest();
        $this->assertNotNull($latest);
        $this->assertArrayHasKey('x_app_usage', $latest);
    }

    #[Test]
    public function it_uploads_media_via_multipart(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'wa');
        file_put_contents($file, 'fake-image-content');

        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'uploaded-media-id'], 200),
        ]);

        $result = (new GraphApiClient)->uploadMedia('token', 'phone-id', $file, 'image/jpeg');

        $this->assertSame('uploaded-media-id', $result['id']);

        @unlink($file);
    }
}
