<?php

namespace Vendor\LaravelWhatsAppCloud\Facades;

use Illuminate\Support\Facades\Facade;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppManager;

/**
 * @method static \Vendor\LaravelWhatsAppCloud\Services\WhatsAppManager account(int|string $identifier)
 * @method static \Vendor\LaravelWhatsAppCloud\Services\WhatsAppManager using(string $name)
 * @method static \Vendor\LaravelWhatsAppCloud\Services\WhatsAppManager queue()
 * @method static \Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage send(string $to, string $message)
 * @method static \Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage sendText(string $to, string $text, bool $previewUrl = false)
 * @method static \Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage sendTemplate(string $to, string $name, string $language = 'en_US', array $components = [])
 * @method static \Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage template(string $to, string $templateName, array $variables = [], ?string $language = null)
 * @method static \Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage sendImage(string $to, string $link, ?string $caption = null)
 * @method static \Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage sendDocument(string $to, string $link, ?string $filename = null, ?string $caption = null)
 * @method static \Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage sendAudio(string $to, string $link)
 * @method static \Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage sendVideo(string $to, string $link, ?string $caption = null)
 * @method static \Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage sendLocation(string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null)
 * @method static \Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage sendImageFile(string $to, string $filePath, ?string $caption = null)
 * @method static \Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage sendDocumentFile(string $to, string $filePath, ?string $filename = null, ?string $caption = null)
 * @method static \Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage sendFile(string $to, string $filePath, ?string $caption = null)
 *
 * @see WhatsAppManager
 */
class WhatsApp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'whatsapp';
    }
}
