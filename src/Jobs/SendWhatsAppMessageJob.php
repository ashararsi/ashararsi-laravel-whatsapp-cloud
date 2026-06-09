<?php

namespace Vendor\LaravelWhatsAppCloud\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppManager;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $accountId,
        public array $payload,
        public string $type,
        public string $to,
        public ?string $message = null,
        public ?int $messageId = null,
    ) {}

    public function handle(WhatsAppManager $manager): void
    {
        $account = WhatsAppAccount::query()->findOrFail($this->accountId);

        $manager->sendNow(
            $account,
            $this->type,
            $this->to,
            $this->message,
            $this->payload,
            $this->messageId,
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('WhatsApp queued message permanently failed', [
            'account_id' => $this->accountId,
            'to' => $this->to,
            'type' => $this->type,
            'message_id' => $this->messageId,
            'error' => $exception->getMessage(),
        ]);

        if ($this->messageId && config('whatsapp.log_messages', true)) {
            WhatsAppMessage::query()
                ->where('id', $this->messageId)
                ->update(['status' => WhatsAppMessage::STATUS_FAILED]);
        }
    }
}
