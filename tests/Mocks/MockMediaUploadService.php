<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Mocks;

use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Services\MediaUploadService;

class MockMediaUploadService extends MediaUploadService
{
    public string $lastMediaId = 'mock-media-id-123';

    public function upload(WhatsAppAccount $account, string $filePath): string
    {
        return $this->lastMediaId;
    }
}
