<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTemplate;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class AdminTemplateTest extends TestCase
{
    protected function seedTemplates(): WhatsAppAccount
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'tpl-account',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'pid',
            'waba_id' => 'waba',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        WhatsAppTemplate::query()->create([
            'account_id' => $account->id,
            'provider' => 'meta',
            'template_name' => 'order_confirmed',
            'category' => WhatsAppTemplate::CATEGORY_UTILITY,
            'language' => 'en_US',
            'status' => WhatsAppTemplate::STATUS_APPROVED,
            'components_json' => [['type' => 'BODY', 'text' => 'Hi {{1}}']],
            'meta_template_id' => 'tpl-1',
            'synced_at' => now(),
        ]);

        WhatsAppTemplate::query()->create([
            'account_id' => $account->id,
            'provider' => 'meta',
            'template_name' => 'promo_sale',
            'category' => WhatsAppTemplate::CATEGORY_MARKETING,
            'language' => 'en_US',
            'status' => WhatsAppTemplate::STATUS_PENDING,
            'synced_at' => now(),
        ]);

        return $account;
    }

    #[Test]
    public function it_lists_templates_in_admin(): void
    {
        $this->seedTemplates();

        $this->get('/admin/whatsapp/templates')
            ->assertOk()
            ->assertSee('order_confirmed')
            ->assertSee('promo_sale')
            ->assertSee('Sync Templates');
    }

    #[Test]
    public function it_searches_templates(): void
    {
        $this->seedTemplates();

        $this->get('/admin/whatsapp/templates?search=order_confirmed')
            ->assertOk()
            ->assertSee('order_confirmed')
            ->assertDontSee('promo_sale');
    }

    #[Test]
    public function it_filters_templates_by_category(): void
    {
        $this->seedTemplates();

        $this->get('/admin/whatsapp/templates?category=MARKETING')
            ->assertOk()
            ->assertSee('promo_sale')
            ->assertDontSee('order_confirmed');
    }

    #[Test]
    public function it_shows_template_details(): void
    {
        $this->seedTemplates();
        $template = WhatsAppTemplate::query()->where('template_name', 'order_confirmed')->firstOrFail();

        $this->get("/admin/whatsapp/templates/{$template->id}")
            ->assertOk()
            ->assertSee('order_confirmed')
            ->assertSee('UTILITY')
            ->assertSee('tpl-1')
            ->assertSee('WhatsApp::template');
    }

    #[Test]
    public function it_syncs_templates_from_admin_form(): void
    {
        $account = $this->seedTemplates();

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => 'new-tpl',
                        'name' => 'auth_code',
                        'language' => 'en_US',
                        'category' => 'AUTHENTICATION',
                        'status' => 'APPROVED',
                        'components' => [],
                    ],
                ],
            ]),
        ]);

        $this->post('/admin/whatsapp/templates/sync', [
            'account_id' => $account->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_templates', [
            'template_name' => 'auth_code',
            'category' => 'AUTHENTICATION',
        ]);
    }

    #[Test]
    public function dashboard_shows_template_stats(): void
    {
        $this->seedTemplates();

        $this->get('/admin/whatsapp')
            ->assertOk()
            ->assertSee('Approved Templates')
            ->assertSee('Pending Templates')
            ->assertSee('Rejected Templates');
    }
}
