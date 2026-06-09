<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTemplate;
use Vendor\LaravelWhatsAppCloud\Services\AnalyticsService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    #[Test]
    public function it_includes_cost_and_delivery_metrics(): void
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'analytics',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'id',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        WhatsAppTemplate::query()->create([
            'account_id' => $account->id,
            'template_name' => 'order_confirmed',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
        ]);

        WhatsAppMessage::query()->create([
            'account_id' => $account->id,
            'to' => '923009999999',
            'type' => 'template',
            'message' => 'order_confirmed',
            'status' => WhatsAppMessage::STATUS_SENT,
            'meta_json' => ['template' => ['name' => 'order_confirmed']],
        ]);

        WhatsAppMessage::query()->create([
            'account_id' => $account->id,
            'to' => '923009999999',
            'type' => 'text',
            'message' => 'Hi',
            'status' => WhatsAppMessage::STATUS_FAILED,
        ]);

        $stats = app(AnalyticsService::class)->overview($account->id);

        $this->assertArrayHasKey('estimated_cost_today', $stats);
        $this->assertArrayHasKey('delivery_rate', $stats);
        $this->assertArrayHasKey('chart_data', $stats);
        $this->assertGreaterThan(0, $stats['messages_today']);
    }

    #[Test]
    public function it_counts_disabled_templates(): void
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'tpl-stats',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'id',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        WhatsAppTemplate::query()->create([
            'account_id' => $account->id,
            'template_name' => 'disabled_tpl',
            'language' => 'en_US',
            'status' => WhatsAppTemplate::STATUS_DISABLED,
        ]);

        $stats = app(AnalyticsService::class)->overview($account->id);

        $this->assertSame(1, $stats['templates_disabled']);
    }
}
