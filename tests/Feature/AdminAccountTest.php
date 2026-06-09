<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class AdminAccountTest extends TestCase
{
    #[Test]
    public function it_lists_accounts_in_admin_panel(): void
    {
        WhatsAppAccount::query()->create([
            'name' => 'primary',
            'provider' => WhatsAppAccount::PROVIDER_META,
            'phone_number' => '923001234567',
            'phone_number_id' => '123',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->get('/admin/whatsapp/accounts');

        $response->assertOk();
        $response->assertSee('primary');
    }

    #[Test]
    public function it_creates_account_via_admin_form(): void
    {
        $response = $this->post('/admin/whatsapp/accounts', [
            'provider' => 'meta',
            'name' => 'support',
            'phone_number' => '923001111111',
            'phone_number_id' => '999',
            'access_token' => 'secret-token-1234567890',
            'webhook_verify_token' => 'verify',
            'is_default' => '1',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('whatsapp.admin.accounts.index'));
        $this->assertDatabaseHas('whatsapp_accounts', [
            'name' => 'support',
            'phone_number_id' => '999',
            'is_default' => true,
        ]);
    }
}
