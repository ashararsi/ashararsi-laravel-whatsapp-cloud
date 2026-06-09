<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppMessageBuilder;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class WhatsAppMessageBuilderTest extends TestCase
{
    #[Test]
    public function it_builds_text_message_payload(): void
    {
        $payload = WhatsAppMessageBuilder::text('923001234567', 'Hello World');

        $this->assertSame('whatsapp', $payload['messaging_product']);
        $this->assertSame('text', $payload['type']);
        $this->assertSame('923001234567', $payload['to']);
        $this->assertSame('Hello World', $payload['text']['body']);
    }

    #[Test]
    public function it_builds_template_message_payload(): void
    {
        $payload = WhatsAppMessageBuilder::template('923001234567', 'hello_world', 'en_US');

        $this->assertSame('template', $payload['type']);
        $this->assertSame('hello_world', $payload['template']['name']);
        $this->assertSame('en_US', $payload['template']['language']['code']);
    }

    #[Test]
    public function it_builds_media_payloads(): void
    {
        $image = WhatsAppMessageBuilder::image('923001234567', 'https://example.com/image.jpg', 'caption');
        $document = WhatsAppMessageBuilder::document('923001234567', 'https://example.com/doc.pdf', 'file.pdf');
        $audio = WhatsAppMessageBuilder::audio('923001234567', 'https://example.com/audio.mp3');
        $video = WhatsAppMessageBuilder::video('923001234567', 'https://example.com/video.mp4');
        $location = WhatsAppMessageBuilder::location('923001234567', 24.86, 67.00, 'Office');

        $this->assertSame('image', $image['type']);
        $this->assertSame('document', $document['type']);
        $this->assertSame('audio', $audio['type']);
        $this->assertSame('video', $video['type']);
        $this->assertSame('location', $location['type']);
    }

    #[Test]
    public function it_normalizes_phone_numbers(): void
    {
        $payload = WhatsAppMessageBuilder::text('+92 300 1234567', 'Hi');

        $this->assertSame('923001234567', $payload['to']);
    }

    #[Test]
    public function it_rejects_invalid_media_urls(): void
    {
        $this->expectException(WhatsAppException::class);

        WhatsAppMessageBuilder::image('923001234567', 'not-a-url');
    }

    #[Test]
    public function it_rejects_invalid_coordinates(): void
    {
        $this->expectException(WhatsAppException::class);

        WhatsAppMessageBuilder::location('923001234567', 120, 0);
    }

    #[Test]
    public function it_builds_interactive_button_and_list_payloads(): void
    {
        $buttons = WhatsAppMessageBuilder::interactiveButtons('923001234567', 'Choose', [
            ['id' => 'yes', 'title' => 'Yes'],
        ]);
        $list = WhatsAppMessageBuilder::interactiveList('923001234567', 'Pick', 'Menu', [
            ['title' => 'Options', 'rows' => [['id' => '1', 'title' => 'One']]],
        ]);

        $this->assertSame('interactive', $buttons['type']);
        $this->assertSame('button', $buttons['interactive']['type']);
        $this->assertSame('interactive', $list['type']);
        $this->assertSame('list', $list['interactive']['type']);
    }
}
