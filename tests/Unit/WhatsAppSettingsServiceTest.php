<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppSettingsService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class WhatsAppSettingsServiceTest extends TestCase
{
    #[Test]
    public function it_loads_runtime_defaults_from_database(): void
    {
        $settings = app(WhatsAppSettingsService::class)->all();

        $this->assertSame(30, $settings['graph_api.timeout']);
        $this->assertSame('meta', $settings['general.default_provider']);
        $this->assertSame('v21.0', $settings['general.api_version']);
        $this->assertTrue($settings['queue.enabled']);
        $this->assertFalse($settings['ai.enabled']);
        $this->assertTrue($settings['auto_reply.enabled']);
        $this->assertTrue($settings['log_messages']);
    }

    #[Test]
    public function it_applies_database_settings_to_runtime_config(): void
    {
        $service = app(WhatsAppSettingsService::class);

        $service->updateMany([
            'general.default_provider' => 'twilio',
            'general.api_version' => 'v22.0',
            'graph_api.timeout' => 45,
            'queue.enabled' => false,
            'ai.enabled' => true,
            'webhook.require_signature' => true,
            'admin.authorization_enabled' => false,
        ]);

        $this->assertSame('twilio', config('whatsapp.default_provider'));
        $this->assertSame('v22.0', config('whatsapp.api_version'));
        $this->assertSame(45, config('whatsapp.graph_api.timeout'));
        $this->assertFalse(config('whatsapp.queue_enabled'));
        $this->assertTrue(config('whatsapp.ai.enabled'));
        $this->assertTrue(config('whatsapp.webhook.require_signature'));
        $this->assertFalse(config('whatsapp.admin.authorization_enabled'));
    }

    #[Test]
    public function it_persists_boolean_settings_as_zero_or_one(): void
    {
        app(WhatsAppSettingsService::class)->updateMany([
            'campaigns.use_queue' => true,
        ]);

        $this->assertDatabaseHas('whatsapp_settings', [
            'key' => 'campaigns.use_queue',
            'value' => '1',
        ]);
    }
}
