<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    #[Test]
    public function it_blocks_admin_access_when_gate_denies(): void
    {
        config(['whatsapp.admin.authorization_enabled' => true]);

        Gate::define('manage-whatsapp', fn () => false);

        $response = $this->get('/admin/whatsapp/accounts');

        $response->assertForbidden();
    }
}
