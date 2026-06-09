<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    #[Test]
    public function settings_page_is_accessible(): void
    {
        $response = $this->get(route('whatsapp.admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('WhatsApp Settings');
        $response->assertSee('Graph API Timeout');
    }

    #[Test]
    public function admin_can_update_settings(): void
    {
        $response = $this->put(route('whatsapp.admin.settings.update'), [
            'graph_api_timeout' => 60,
            'graph_api_max_retries' => 4,
            'graph_api_retry_base_delay_ms' => 2000,
            'graph_api_retry_max_delay_ms' => 120000,
            'cost_utility' => 0.006,
            'cost_marketing' => 0.016,
            'cost_authentication' => 0.005,
            'cost_service' => 0.001,
            'queue_tries' => 5,
        ]);

        $response->assertRedirect(route('whatsapp.admin.settings.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_settings', [
            'key' => 'graph_api.timeout',
            'value' => '60',
        ]);

        $this->assertSame(60, config('whatsapp.graph_api.timeout'));
        $this->assertSame(5, config('whatsapp.queue.tries'));
    }
}
