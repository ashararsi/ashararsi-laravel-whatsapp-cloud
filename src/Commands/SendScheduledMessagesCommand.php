<?php

namespace Vendor\LaravelWhatsAppCloud\Commands;

use Illuminate\Console\Command;
use Vendor\LaravelWhatsAppCloud\Services\ScheduledMessageService;

class SendScheduledMessagesCommand extends Command
{
    protected $signature = 'whatsapp:scheduled:send';

    protected $description = 'Send due scheduled WhatsApp messages';

    public function handle(ScheduledMessageService $scheduler): int
    {
        $count = $scheduler->processDue();
        $this->info("Sent {$count} scheduled message(s).");

        return self::SUCCESS;
    }
}
