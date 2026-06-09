<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class SyncCommandsTest extends TestCase
{
    protected function metaAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'cmd-sync',
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
    public function sync_business_command_runs(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push(['name' => 'CLI Biz', 'account_review_status' => 'APPROVED'])
                ->push(['verified_name' => 'CLI', 'quality_rating' => 'GREEN'])
                ->push(['data' => []]),
        ]);

        $this->metaAccount();

        $this->artisan('whatsapp:sync-business')
            ->assertSuccessful();
    }

    #[Test]
    public function sync_numbers_command_runs(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    ['id' => 'phone-123', 'display_phone_number' => '+1', 'verified_name' => 'N', 'status' => 'CONNECTED'],
                ],
            ]),
        ]);

        $this->metaAccount();

        $this->artisan('whatsapp:sync-numbers')
            ->assertSuccessful();
    }
}
