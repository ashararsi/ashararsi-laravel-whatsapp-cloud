<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Events\TemplateSynced;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTemplate;
use Vendor\LaravelWhatsAppCloud\Services\TemplateSyncService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class TemplateSyncServiceTest extends TestCase
{
    protected function metaAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'meta-templates',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'phone-id',
            'waba_id' => 'waba-123',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_syncs_templates_from_meta_api(): void
    {
        Event::fake([TemplateSynced::class]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => 'meta-tpl-1',
                        'name' => 'order_confirmed',
                        'language' => 'en_US',
                        'category' => 'UTILITY',
                        'status' => 'APPROVED',
                        'components' => [
                            ['type' => 'BODY', 'text' => 'Hi {{1}}, order {{2}} confirmed.'],
                        ],
                    ],
                ],
            ]),
        ]);

        $account = $this->metaAccount();
        $count = app(TemplateSyncService::class)->syncAccount($account);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('whatsapp_templates', [
            'account_id' => $account->id,
            'template_name' => 'order_confirmed',
            'provider' => 'meta',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'meta_template_id' => 'meta-tpl-1',
        ]);

        Event::assertDispatched(TemplateSynced::class);
    }

    #[Test]
    public function it_resolves_accounts_by_provider_filter(): void
    {
        $this->metaAccount();
        WhatsAppAccount::query()->create([
            'name' => 'twilio',
            'provider' => 'twilio',
            'phone_number' => '923001234567',
            'twilio_sid' => 'AC1',
            'twilio_token' => 'token-1234567890',
            'twilio_whatsapp_number' => '14155238886',
            'is_default' => false,
            'is_active' => true,
        ]);

        $accounts = app(TemplateSyncService::class)->resolveAccountsForSync(null, 'meta');

        $this->assertCount(1, $accounts);
        $this->assertSame('meta', $accounts->first()->provider);
    }

    #[Test]
    public function it_updates_existing_templates_on_resync(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => 'meta-tpl-2',
                        'name' => 'hello_world',
                        'language' => 'en_US',
                        'category' => 'MARKETING',
                        'status' => 'PENDING',
                        'components' => [],
                    ],
                ],
            ]),
        ]);

        $account = $this->metaAccount();
        WhatsAppTemplate::query()->create([
            'account_id' => $account->id,
            'provider' => 'meta',
            'template_name' => 'hello_world',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
        ]);

        app(TemplateSyncService::class)->syncAccount($account);

        $this->assertDatabaseHas('whatsapp_templates', [
            'template_name' => 'hello_world',
            'category' => 'MARKETING',
            'status' => 'PENDING',
            'meta_template_id' => 'meta-tpl-2',
        ]);
    }
}
