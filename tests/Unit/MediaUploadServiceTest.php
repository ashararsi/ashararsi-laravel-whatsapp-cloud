<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Services\MediaUploadService;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class MediaUploadServiceTest extends TestCase
{
    protected function metaAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'media-upload',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'phone-id',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_uploads_file_and_returns_media_id(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'wa');
        file_put_contents($file, 'image-bytes');

        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'meta-media-99'], 200),
        ]);

        $mediaId = app(MediaUploadService::class)->upload($this->metaAccount(), $file);

        $this->assertSame('meta-media-99', $mediaId);

        @unlink($file);
    }

    #[Test]
    public function it_rejects_missing_file(): void
    {
        $this->expectException(WhatsAppException::class);

        app(MediaUploadService::class)->upload($this->metaAccount(), '/nonexistent/file.jpg');
    }

    #[Test]
    public function it_rejects_twilio_accounts(): void
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'twilio',
            'provider' => 'twilio',
            'phone_number' => '923001234567',
            'twilio_sid' => 'AC1',
            'twilio_token' => 'token-1234567890',
            'twilio_whatsapp_number' => '14155238886',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->expectException(WhatsAppException::class);

        app(MediaUploadService::class)->upload($account, __FILE__);
    }
}
