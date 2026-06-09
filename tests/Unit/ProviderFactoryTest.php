<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppProviderInterface;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Providers\MetaProvider;
use Vendor\LaravelWhatsAppCloud\Providers\TwilioProvider;
use Vendor\LaravelWhatsAppCloud\Services\ProviderFactory;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class ProviderFactoryTest extends TestCase
{
    #[Test]
    public function it_makes_meta_provider(): void
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'meta-factory',
            'provider' => WhatsAppAccount::PROVIDER_META,
            'phone_number' => '923001234567',
            'phone_number_id' => 'meta-id',
            'access_token' => 'meta-token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $provider = ProviderFactory::make($account);

        $this->assertInstanceOf(MetaProvider::class, $provider);
        $this->assertInstanceOf(WhatsAppProviderInterface::class, $provider);
    }

    #[Test]
    public function it_makes_twilio_provider(): void
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'twilio-factory',
            'provider' => WhatsAppAccount::PROVIDER_TWILIO,
            'phone_number' => '923001234567',
            'twilio_sid' => 'AC123',
            'twilio_token' => 'twilio-token-1234567890',
            'twilio_whatsapp_number' => '14155238886',
            'is_default' => true,
            'is_active' => true,
        ]);

        $provider = ProviderFactory::make($account);

        $this->assertInstanceOf(TwilioProvider::class, $provider);
    }

    #[Test]
    public function it_defaults_to_meta_for_legacy_accounts(): void
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'legacy',
            'phone_number' => '923001234567',
            'phone_number_id' => 'legacy-id',
            'access_token' => 'legacy-token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $provider = ProviderFactory::make($account);

        $this->assertInstanceOf(MetaProvider::class, $provider);
    }
}
