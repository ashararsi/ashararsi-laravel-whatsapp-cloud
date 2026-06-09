<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Support\IncomingMessageParser;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class IncomingMessageParserTest extends TestCase
{
    #[Test]
    public function it_parses_incoming_text_message(): void
    {
        $parsed = IncomingMessageParser::parse([
            'from' => '923009999999',
            'id' => 'wamid.incoming1',
            'type' => 'text',
            'text' => ['body' => 'Hello there'],
        ], [
            ['wa_id' => '923009999999', 'profile' => ['name' => 'Ali']],
        ]);

        $this->assertSame('923009999999', $parsed['phone']);
        $this->assertSame('Ali', $parsed['name']);
        $this->assertSame('text', $parsed['type']);
        $this->assertSame('Hello there', $parsed['body']);
        $this->assertSame('wamid.incoming1', $parsed['whatsapp_message_id']);
    }

    #[Test]
    public function it_parses_image_message_without_caption(): void
    {
        $parsed = IncomingMessageParser::parse([
            'from' => '923001234567',
            'type' => 'image',
            'image' => ['id' => 'img123'],
        ]);

        $this->assertSame('[Image]', $parsed['body']);
    }
}
