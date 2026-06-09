<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTemplate;
use Vendor\LaravelWhatsAppCloud\Tests\Mocks\MockWhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class WhatsAppTemplateApiTest extends TestCase
{
    #[Test]
    public function it_sends_template_with_simple_variables(): void
    {
        $mock = new MockWhatsAppClient;
        $this->app->instance(WhatsAppClientInterface::class, $mock);

        $account = WhatsAppAccount::query()->create([
            'name' => 'tpl-send',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'pid',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        WhatsAppTemplate::query()->create([
            'account_id' => $account->id,
            'provider' => 'meta',
            'template_name' => 'order_confirmed',
            'language' => 'en_US',
            'status' => WhatsAppTemplate::STATUS_APPROVED,
        ]);

        $message = WhatsApp::template('923009999999', 'order_confirmed', ['Ali', '#12345']);

        $this->assertSame('template', $message->type);
        $this->assertSame('sent', $message->status);
        $this->assertCount(1, $mock->sent);

        $payload = $mock->sent[0]['payload'];
        $this->assertSame('order_confirmed', $payload['template']['name']);
        $this->assertSame('Ali', $payload['template']['components'][0]['parameters'][0]['text']);
        $this->assertSame('#12345', $payload['template']['components'][0]['parameters'][1]['text']);
    }
}
