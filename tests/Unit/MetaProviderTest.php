<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Providers\MetaProvider;
use Vendor\LaravelWhatsAppCloud\Services\MediaUploadService;
use Vendor\LaravelWhatsAppCloud\Tests\Mocks\MockWhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class MetaProviderTest extends TestCase
{
    #[Test]
    public function it_sends_text_via_meta_client(): void
    {
        $mock = new MockWhatsAppClient;
        $this->app->instance(WhatsAppClientInterface::class, $mock);

        $account = WhatsAppAccount::query()->create([
            'name' => 'meta',
            'provider' => WhatsAppAccount::PROVIDER_META,
            'phone_number' => '923001234567',
            'phone_number_id' => 'meta-phone-id',
            'access_token' => 'meta-token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $provider = new MetaProvider($account, $mock, app(MediaUploadService::class));
        $result = $provider->sendText('923009999999', 'Hello Meta');

        $this->assertSame('text', $result->payload['type']);
        $this->assertStringStartsWith('wamid.mock', $result->response['messages'][0]['id']);
        $this->assertCount(1, $mock->sent);
    }

    #[Test]
    public function it_builds_payload_without_sending(): void
    {
        $mock = new MockWhatsAppClient;
        $account = WhatsAppAccount::query()->create([
            'name' => 'meta-build',
            'provider' => WhatsAppAccount::PROVIDER_META,
            'phone_number' => '923001234567',
            'phone_number_id' => 'meta-phone-id',
            'access_token' => 'meta-token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $provider = new MetaProvider($account, $mock, app(MediaUploadService::class));
        $payload = $provider->buildPayload('text', '923009999999', ['text' => 'Queued']);

        $this->assertSame('text', $payload['type']);
        $this->assertCount(0, $mock->sent);
    }
}
