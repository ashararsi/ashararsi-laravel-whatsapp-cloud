<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppSettingsService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    protected function settingsPayload(array $overrides = []): array
    {
        $payload = [];

        foreach (app(WhatsAppSettingsService::class)->definitions() as $key => $definition) {
            $field = str_replace('.', '_', $key);
            $default = $definition['default'];

            if ($definition['type'] === 'boolean') {
                $payload[$field] = ($default ?? false) ? '1' : '0';
            } else {
                $payload[$field] = $default;
            }
        }

        return array_merge($payload, $overrides);
    }

    #[Test]
    public function settings_page_is_accessible(): void
    {
        $response = $this->get(route('whatsapp.admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('WhatsApp Settings');
        $response->assertSee('Default Provider');
        $response->assertSee('Require Meta Webhook Signature');
    }

    #[Test]
    public function admin_can_update_settings(): void
    {
        $response = $this->put(route('whatsapp.admin.settings.update'), $this->settingsPayload([
            'graph_api_timeout' => 60,
            'queue_tries' => 5,
            'general_default_provider' => 'meta',
            'general_api_version' => 'v21.0',
            'webhook_require_signature' => '1',
            'ai_enabled' => '1',
            'campaigns_use_queue' => '1',
        ]));

        $response->assertRedirect(route('whatsapp.admin.settings.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_settings', [
            'key' => 'graph_api.timeout',
            'value' => '60',
        ]);

        $this->assertSame(60, config('whatsapp.graph_api.timeout'));
        $this->assertSame(5, config('whatsapp.queue.tries'));
        $this->assertTrue(config('whatsapp.webhook.require_signature'));
        $this->assertTrue(config('whatsapp.ai.enabled'));
        $this->assertTrue(config('whatsapp.campaigns.use_queue'));
    }
}
