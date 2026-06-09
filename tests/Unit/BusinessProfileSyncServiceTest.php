<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Events\BusinessProfileSynced;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Services\BusinessProfileSyncService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class BusinessProfileSyncServiceTest extends TestCase
{
    protected function metaAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'biz-sync',
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
    public function it_syncs_business_profile_from_meta(): void
    {
        Event::fake([BusinessProfileSynced::class]);

        Http::fake([
            'graph.facebook.com/*/waba-456*' => Http::response([
                'name' => 'Acme Corp',
                'account_review_status' => 'APPROVED',
            ]),
            'graph.facebook.com/*/phone-123*' => Http::response([
                'verified_name' => 'Acme Support',
                'quality_rating' => 'GREEN',
                'messaging_limit_tier' => 'TIER_1K',
                'status' => 'CONNECTED',
            ]),
            'graph.facebook.com/*/whatsapp_business_profile*' => Http::response([
                'data' => [['about' => 'We help customers']],
            ]),
        ]);

        $profile = app(BusinessProfileSyncService::class)->syncAccount($this->metaAccount());

        $this->assertSame('Acme Corp', $profile->business_name);
        $this->assertSame('Acme Support', $profile->display_name);
        $this->assertSame('GREEN', $profile->quality_rating);
        $this->assertSame('TIER_1K', $profile->messaging_tier);
        $this->assertDatabaseHas('whatsapp_business_profiles', ['business_name' => 'Acme Corp']);

        Event::assertDispatched(BusinessProfileSynced::class);
    }
}
