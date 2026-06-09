<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Tests\Mocks\MockWhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class MediaUploadApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(WhatsAppClientInterface::class, new MockWhatsAppClient);
    }

    protected function createAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'media-api',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'phone-id',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_sends_image_file_via_upload_then_message(): void
    {
        $this->createAccount();
        $file = tempnam(sys_get_temp_dir(), 'wa');
        file_put_contents($file, 'png-content');

        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'uploaded-id'], 200),
        ]);

        $message = WhatsApp::sendImageFile('923009999999', $file, 'Caption');

        $this->assertSame('sent', $message->status);
        $this->assertSame('image', $message->type);
        $this->assertSame('uploaded-id', $message->meta_json['image']['id'] ?? null);

        @unlink($file);
    }

    #[Test]
    public function it_sends_document_file_via_upload(): void
    {
        $this->createAccount();
        $file = tempnam(sys_get_temp_dir(), 'wa');
        file_put_contents($file, 'pdf-content');

        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'doc-media-id'], 200),
        ]);

        $message = WhatsApp::sendDocumentFile('923009999999', $file, 'invoice.pdf');

        $this->assertSame('sent', $message->status);
        $this->assertSame('document', $message->type);

        @unlink($file);
    }

    #[Test]
    public function send_file_auto_detects_image(): void
    {
        $this->createAccount();
        $file = sys_get_temp_dir().'/test-image-'.uniqid().'.png';
        file_put_contents($file, "\x89PNG\r\n\x1a\n");

        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'auto-id'], 200),
        ]);

        $message = WhatsApp::sendFile('923009999999', $file);

        $this->assertSame('image', $message->type);

        @unlink($file);
    }
}
