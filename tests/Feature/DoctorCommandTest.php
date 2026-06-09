<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class DoctorCommandTest extends TestCase
{
    #[Test]
    public function it_runs_whatsapp_doctor_command(): void
    {
        WhatsAppAccount::query()->create([
            'name' => 'doctor-meta',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => '12345',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->artisan('whatsapp:doctor')
            ->assertExitCode(0)
            ->expectsOutputToContain('WhatsApp Cloud Doctor Report')
            ->expectsOutputToContain('[PASS]');
    }

    #[Test]
    public function it_reports_errors_for_incomplete_meta_account_when_signature_required(): void
    {
        config([
            'whatsapp.webhook.require_signature' => true,
            'whatsapp.webhook.app_secret' => null,
        ]);

        WhatsAppAccount::query()->create([
            'name' => 'incomplete',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => null,
            'access_token' => null,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->artisan('whatsapp:doctor')
            ->assertExitCode(1)
            ->expectsOutputToContain('[ERROR]');
    }
}
