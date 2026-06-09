<?php

namespace Vendor\LaravelWhatsAppCloud\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMediaFile;

class TranscriptionCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WhatsAppMediaFile $file,
        public string $text,
    ) {}
}
