<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Vendor\LaravelWhatsAppCloud\Events\ScheduledMessageSent;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppScheduledMessage;

class ScheduledMessageService
{
    public function processDue(): int
    {
        $count = 0;

        WhatsAppScheduledMessage::query()
            ->where('status', WhatsAppScheduledMessage::STATUS_PENDING)
            ->where('send_at', '<=', now())
            ->orderBy('send_at')
            ->chunkById(50, function ($messages) use (&$count) {
                foreach ($messages as $scheduled) {
                    try {
                        WhatsApp::account($scheduled->account_id)
                            ->sendText($scheduled->to, (string) $scheduled->message);

                        $scheduled->update([
                            'status' => WhatsAppScheduledMessage::STATUS_SENT,
                            'sent_at' => now(),
                        ]);

                        event(new ScheduledMessageSent($scheduled));
                        $count++;
                    } catch (\Throwable $e) {
                        $scheduled->update(['status' => WhatsAppScheduledMessage::STATUS_FAILED]);
                    }
                }
            });

        return $count;
    }
}
