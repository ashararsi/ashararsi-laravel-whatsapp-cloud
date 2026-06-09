<?php

namespace Vendor\LaravelWhatsAppCloud\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Vendor\LaravelWhatsAppCloud\Events\MessageDeadLettered;
use Vendor\LaravelWhatsAppCloud\Events\MessageSendFailed;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppManager;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    /** @var array<int, int> */
    public array $backoff;

    public int $maxExceptions = 3;

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
    ) {
        $this->tries = max(1, (int) config('whatsapp.queue.tries', 3));
        $this->backoff = config('whatsapp.queue.backoff', [10, 30, 60]);
    }

    public function handle(WhatsAppManager $manager): void
    {
        $account = WhatsAppAccount::query()->findOrFail($this->accountId);

        try {
            $manager->sendNow(
                $account,
                $this->type,
                $this->to,
                $this->message,
                $this->payload,
                $this->messageId,
            );
        } catch (\Throwable $e) {
            $this->recordFailure($account, $e);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $account = WhatsAppAccount::query()->find($this->accountId);

        Log::error('WhatsApp queued message permanently failed', [
            'account_id' => $this->accountId,
            'to' => $this->to,
            'type' => $this->type,
            'message_id' => $this->messageId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        if ($this->messageId && config('whatsapp.log_messages', true)) {
            WhatsAppMessage::query()
                ->where('id', $this->messageId)
                ->update([
                    'status' => WhatsAppMessage::STATUS_FAILED,
                    'last_error' => $exception->getMessage(),
                    'dead_lettered_at' => now(),
                ]);
        }

        if ($account) {
            event(new MessageDeadLettered(
                $account,
                $this->to,
                $this->type,
                $this->messageId,
                $exception->getMessage(),
            ));
        }
    }

    protected function recordFailure(WhatsAppAccount $account, \Throwable $e): void
    {
        $error = $e instanceof WhatsAppException
            ? ($e->response['error']['message'] ?? $e->getMessage())
            : $e->getMessage();

        if ($this->messageId && config('whatsapp.log_messages', true)) {
            WhatsAppMessage::query()
                ->where('id', $this->messageId)
                ->update([
                    'retry_count' => $this->attempts(),
                    'last_error' => $error,
                ]);
        }

        event(new MessageSendFailed(
            $account,
            $this->to,
            $this->type,
            $this->messageId,
            $error,
            $this->attempts(),
        ));
    }
}
