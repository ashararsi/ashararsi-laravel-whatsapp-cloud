<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Services\ConversationService;
use Vendor\LaravelWhatsAppCloud\Services\DashboardService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    #[Test]
    public function it_returns_dashboard_stats(): void
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'meta',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'id',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $service = new ConversationService;
        $service->recordIncoming($account, [
            'from' => '923009999999',
            'id' => 'wamid.1',
            'type' => 'text',
            'text' => ['body' => 'Hi'],
        ]);
        $service->recordOutgoing($account, '923009999999', 'text', 'Hello', [], 'wamid.2');

        $stats = (new DashboardService)->stats();

        $this->assertSame(1, $stats['total_contacts']);
        $this->assertSame(1, $stats['total_conversations']);
        $this->assertSame(1, $stats['incoming_today']);
        $this->assertSame(1, $stats['outgoing_today']);
    }
}
