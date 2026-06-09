<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppSettingsService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class WhatsAppSettingsServiceTest extends TestCase
{
    #[Test]
    public function it_loads_defaults_from_database(): void
    {
        $settings = app(WhatsAppSettingsService::class)->all();

        $this->assertSame(30, $settings['graph_api.timeout']);
        $this->assertSame(3, $settings['graph_api.max_retries']);
        $this->assertSame(0.005, $settings['cost.utility']);
        $this->assertSame(0.015, $settings['cost.marketing']);
        $this->assertSame(3, $settings['queue.tries']);
    }

    #[Test]
    public function it_applies_database_settings_to_runtime_config(): void
    {
        $service = app(WhatsAppSettingsService::class);

        $service->updateMany([
            'graph_api.timeout' => 45,
            'graph_api.max_retries' => 5,
            'cost.utility' => 0.01,
            'cost.marketing' => 0.02,
            'queue.tries' => 7,
        ]);

        $this->assertSame(45, config('whatsapp.graph_api.timeout'));
        $this->assertSame(5, config('whatsapp.graph_api.max_retries'));
        $this->assertSame(0.01, config('whatsapp.cost.utility'));
        $this->assertSame(0.02, config('whatsapp.cost.marketing'));
        $this->assertSame(7, config('whatsapp.queue.tries'));
    }

    #[Test]
    public function it_persists_updates_in_database(): void
    {
        app(WhatsAppSettingsService::class)->updateMany([
            'queue.tries' => 9,
        ]);

        $this->assertDatabaseHas('whatsapp_settings', [
            'key' => 'queue.tries',
            'value' => '9',
        ]);
    }
}
