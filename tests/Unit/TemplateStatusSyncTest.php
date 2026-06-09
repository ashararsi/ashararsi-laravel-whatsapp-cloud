<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTemplate;
use Vendor\LaravelWhatsAppCloud\Services\TemplateSyncService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class TemplateStatusSyncTest extends TestCase
{
    #[Test]
    public function it_syncs_disabled_template_status(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => 'tpl-disabled',
                        'name' => 'paused_promo',
                        'language' => 'en_US',
                        'category' => 'MARKETING',
                        'status' => 'DISABLED',
                        'components' => [],
                    ],
                ],
            ]),
        ]);

        $account = WhatsAppAccount::query()->create([
            'name' => 'status-sync',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'phone-id',
            'waba_id' => 'waba-123',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        app(TemplateSyncService::class)->syncAccount($account);

        $this->assertDatabaseHas('whatsapp_templates', [
            'template_name' => 'paused_promo',
            'status' => WhatsAppTemplate::STATUS_DISABLED,
        ]);
    }
}
