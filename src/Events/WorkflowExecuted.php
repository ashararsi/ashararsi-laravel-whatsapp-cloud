<?php

namespace Vendor\LaravelWhatsAppCloud\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAiWorkflow;

class WorkflowExecuted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WhatsAppAiWorkflow $workflow,
        public string $phone,
        public string $incomingMessage,
        public string $reply,
    ) {}
}
