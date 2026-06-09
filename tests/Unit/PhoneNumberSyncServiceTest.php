<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Events\PhoneNumbersSynced;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Services\PhoneNumberSyncService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class PhoneNumberSyncServiceTest extends TestCase
{
    protected function metaAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'phone-sync',
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
    public function it_syncs_phone_numbers_from_meta(): void
    {
        Event::fake([PhoneNumbersSynced::class]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => 'phone-123',
                        'display_phone_number' => '+92 300 1234567',
                        'verified_name' => 'Acme',
                        'status' => 'CONNECTED',
                        'quality_rating' => 'GREEN',
                        'messaging_limit_tier' => 'TIER_1K',
                    ],
                ],
            ]),
        ]);

        $count = app(PhoneNumberSyncService::class)->syncAccount($this->metaAccount());

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('whatsapp_synced_phone_numbers', [
            'phone_number_id' => 'phone-123',
            'verified_name' => 'Acme',
        ]);

        Event::assertDispatched(PhoneNumbersSynced::class);
    }
}
