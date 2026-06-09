<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Services\SystemHealthService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class SystemHealthServiceTest extends TestCase
{
    #[Test]
    public function it_returns_system_health_overview(): void
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'health',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'id',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        WhatsAppMessage::query()->create([
            'account_id' => $account->id,
            'to' => '923001234567',
            'type' => 'text',
            'message' => 'test',
            'status' => WhatsAppMessage::STATUS_FAILED,
            'dead_lettered_at' => now(),
        ]);

        $health = app(SystemHealthService::class)->overview();

        $this->assertArrayHasKey('queue', $health);
        $this->assertArrayHasKey('webhook', $health);
        $this->assertArrayHasKey('api', $health);
        $this->assertArrayHasKey('rate_limits', $health);
        $this->assertArrayHasKey('failed_jobs', $health);
    }
}
