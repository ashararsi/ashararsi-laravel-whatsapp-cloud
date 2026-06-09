<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class AdminSystemTest extends TestCase
{
    #[Test]
    public function system_monitoring_page_is_accessible(): void
    {
        $response = $this->get(route('whatsapp.admin.system'));

        $response->assertOk();
        $response->assertSee('System Monitoring');
        $response->assertSee('Queue Health');
    }
}
