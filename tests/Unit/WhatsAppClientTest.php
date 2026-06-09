<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class WhatsAppClientTest extends TestCase
{
    #[Test]
    public function it_sends_request_to_meta_graph_api(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.HBgL']],
            ], 200),
        ]);

        $client = new WhatsAppClient;
        $response = $client->send('phone-id', 'token', [
            'messaging_product' => 'whatsapp',
            'to' => '923001234567',
            'type' => 'text',
            'text' => ['body' => 'Hi'],
        ]);

        $this->assertSame('wamid.HBgL', $response['messages'][0]['id']);
    }

    #[Test]
    public function it_throws_on_api_failure(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Invalid OAuth access token'],
            ], 401),
        ]);

        $this->expectException(WhatsAppException::class);

        (new WhatsAppClient)->send('phone-id', 'bad-token', [
            'messaging_product' => 'whatsapp',
            'to' => '923001234567',
            'type' => 'text',
            'text' => ['body' => 'Hi'],
        ]);
    }
}
