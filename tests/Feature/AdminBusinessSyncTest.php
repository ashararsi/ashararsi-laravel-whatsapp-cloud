<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class AdminBusinessSyncTest extends TestCase
{
    protected function metaAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'admin-biz',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'phone-123',
            'waba_id' => 'waba-456',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function admin_can_sync_business_profile(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push(['name' => 'Biz', 'account_review_status' => 'APPROVED'])
                ->push(['verified_name' => 'Display', 'quality_rating' => 'GREEN', 'messaging_limit_tier' => 'TIER_1K'])
                ->push(['data' => []]),
        ]);

        $account = $this->metaAccount();

        $response = $this->post(route('whatsapp.admin.accounts.sync-business', $account));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('whatsapp_business_profiles', ['account_id' => $account->id]);
    }

    #[Test]
    public function admin_can_sync_phone_numbers(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    ['id' => 'phone-123', 'display_phone_number' => '+92 300', 'verified_name' => 'Acme', 'status' => 'CONNECTED'],
                ],
            ]),
        ]);

        $account = $this->metaAccount();

        $response = $this->post(route('whatsapp.admin.accounts.sync-numbers', $account));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('whatsapp_synced_phone_numbers', ['phone_number_id' => 'phone-123']);
    }
}
