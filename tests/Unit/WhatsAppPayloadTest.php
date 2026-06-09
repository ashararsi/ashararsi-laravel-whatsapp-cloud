<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Support\WhatsAppPayload;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class WhatsAppPayloadTest extends TestCase
{
    #[Test]
    public function it_extracts_whatsapp_message_id_from_response(): void
    {
        $id = WhatsAppPayload::extractMessageId([
            'messages' => [['id' => 'wamid.abc123']],
        ]);

        $this->assertSame('wamid.abc123', $id);
    }

    #[Test]
    public function it_extracts_twilio_sid_from_response(): void
    {
        $id = WhatsAppPayload::extractMessageId([
            'sid' => 'SMtwilio123',
            'status' => 'queued',
        ]);

        $this->assertSame('SMtwilio123', $id);
    }
}
