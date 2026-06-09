<?php

namespace Vendor\LaravelWhatsAppCloud\Filament;

/**
 * Optional Filament v3/v4 integration stub.
 *
 * Register in your panel provider:
 * ->plugin(WhatsAppFilamentPlugin::make())
 *
 * Requires: composer require filament/filament
 */
class WhatsAppFilamentPlugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'laravel-whatsapp-cloud';
    }
}
