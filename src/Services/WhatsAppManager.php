<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Support\Facades\Log;
use Vendor\LaravelWhatsAppCloud\Contracts\AccountResolverInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\ConversationRecorderInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\MessageLoggerInterface;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Jobs\SendWhatsAppMessageJob;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Support\ProviderResult;
use Vendor\LaravelWhatsAppCloud\Support\WhatsAppPayload;

class WhatsAppManager
{
    protected bool $queued = false;

    protected int|string|null $accountIdentifier = null;

    public function __construct(
        protected ProviderFactory $providerFactory,
        protected AccountResolverInterface $accountResolver,
        protected MessageLoggerInterface $messageLogger,
        protected ConversationRecorderInterface $conversationRecorder,
    ) {}

    public function account(int|string $identifier): self
    {
        $clone = clone $this;
        $clone->accountIdentifier = $identifier;

        return $clone;
    }

    public function using(string $name): self
    {
        return $this->account($name);
    }

    public function queue(): self
    {
        $clone = clone $this;
        $clone->queued = true;

        return $clone;
    }

    public function send(string $to, string $message): WhatsAppMessage
    {
        return $this->sendText($to, $message);
    }

    public function sendText(string $to, string $text, bool $previewUrl = false): WhatsAppMessage
    {
        return $this->dispatch(
            'text',
            $to,
            $text,
            ['text' => $text, 'preview_url' => $previewUrl],
            fn ($provider) => $provider->sendText($to, $text, $previewUrl),
        );
    }

    /**
     * @param  array<string, mixed>  $components
     */
    public function sendTemplate(
        string $to,
        string $name,
        string $language = 'en_US',
        array $components = [],
    ): WhatsAppMessage {
        return $this->dispatch(
            'template',
            $to,
            $name,
            ['name' => $name, 'language' => $language, 'components' => $components],
            fn ($provider) => $provider->sendTemplate($to, $name, $language, $components),
        );
    }

    public function sendImage(string $to, string $link, ?string $caption = null): WhatsAppMessage
    {
        return $this->dispatch(
            'image',
            $to,
            $caption ?? $link,
            ['link' => $link, 'caption' => $caption],
            fn ($provider) => $provider->sendImage($to, $link, $caption),
        );
    }

    public function sendDocument(
        string $to,
        string $link,
        ?string $filename = null,
        ?string $caption = null,
    ): WhatsAppMessage {
        return $this->dispatch(
            'document',
            $to,
            $filename ?? $link,
            ['link' => $link, 'filename' => $filename, 'caption' => $caption],
            fn ($provider) => $provider->sendDocument($to, $link, $filename, $caption),
        );
    }

    public function sendAudio(string $to, string $link): WhatsAppMessage
    {
        return $this->dispatch(
            'audio',
            $to,
            $link,
            ['link' => $link],
            fn ($provider) => $provider->sendAudio($to, $link),
        );
    }

    public function sendVideo(string $to, string $link, ?string $caption = null): WhatsAppMessage
    {
        return $this->dispatch(
            'video',
            $to,
            $caption ?? $link,
            ['link' => $link, 'caption' => $caption],
            fn ($provider) => $provider->sendVideo($to, $link, $caption),
        );
    }

    public function sendLocation(
        string $to,
        float $latitude,
        float $longitude,
        ?string $name = null,
        ?string $address = null,
    ): WhatsAppMessage {
        $summary = $name ?? "{$latitude},{$longitude}";

        return $this->dispatch(
            'location',
            $to,
            $summary,
            [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'name' => $name,
                'address' => $address,
            ],
            fn ($provider) => $provider->sendLocation($to, $latitude, $longitude, $name, $address),
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  callable(\Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppProviderInterface): ProviderResult  $sender
     */
    protected function dispatch(
        string $type,
        string $to,
        ?string $message,
        array $options,
        callable $sender,
    ): WhatsAppMessage {
        $account = $this->accountResolver->resolve($this->accountIdentifier);
        $provider = $this->providerFactory->resolve($account);

        if ($this->shouldQueue()) {
            $payload = $provider->buildPayload($type, $to, $options);

            $log = $this->messageLogger->log(
                $account,
                $to,
                $type,
                $message,
                $payload,
                null,
                WhatsAppMessage::STATUS_PENDING,
            );

            $job = new SendWhatsAppMessageJob(
                accountId: $account->id,
                payload: $payload,
                type: $type,
                to: $to,
                message: $message,
                messageId: $log->id ?? null,
            );

            $connection = config('whatsapp.queue_connection');
            $queue = config('whatsapp.queue_name', 'default');

            if ($connection) {
                dispatch($job)->onConnection($connection)->onQueue($queue);
            } else {
                dispatch($job)->onQueue($queue);
            }

            return $log;
        }

        return $this->sendNow($account, $type, $to, $message, $sender);
    }

    /**
     * @param  callable(\Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppProviderInterface): ProviderResult|null  $sender
     * @param  array<string, mixed>|null  $payload
     */
    public function sendNow(
        WhatsAppAccount $account,
        string $type,
        string $to,
        ?string $message,
        callable|array|null $senderOrPayload = null,
        ?int $messageId = null,
    ): WhatsAppMessage {
        $provider = $this->providerFactory->resolve($account);

        try {
            if (is_array($senderOrPayload)) {
                $result = $provider->sendPayload($to, $type, $senderOrPayload);
            } else {
                $result = $senderOrPayload($provider);
            }

            $wamid = WhatsAppPayload::extractMessageId($result->response);

            if ($messageId && config('whatsapp.log_messages', true)) {
                $log = WhatsAppMessage::query()->find($messageId);

                if ($log) {
                    $log->update([
                        'status' => WhatsAppMessage::STATUS_SENT,
                        'meta_json' => $result->payload,
                        'response_json' => $result->response,
                        'whatsapp_message_id' => $wamid,
                    ]);

                    $this->recordOutgoingConversation($account, $to, $type, $message, $result->payload, $wamid);

                    return $log->fresh();
                }
            }

            $log = $this->messageLogger->log(
                $account,
                $to,
                $type,
                $message,
                $result->payload,
                $result->response,
                WhatsAppMessage::STATUS_SENT,
                $wamid,
            );

            $this->recordOutgoingConversation($account, $to, $type, $message, $result->payload, $wamid);

            return $log;
        } catch (\Throwable $e) {
            $response = $e instanceof WhatsAppException
                ? $e->response
                : ['error' => $e->getMessage()];

            Log::error('WhatsApp message failed', [
                'account_id' => $account->id,
                'provider' => $account->provider,
                'to' => $to,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            if ($messageId && config('whatsapp.log_messages', true)) {
                $log = WhatsAppMessage::query()->find($messageId);

                if ($log) {
                    $log->update([
                        'status' => WhatsAppMessage::STATUS_FAILED,
                        'response_json' => $response,
                    ]);

                    throw $e;
                }
            }

            $payload = is_array($senderOrPayload) ? $senderOrPayload : [];

            $this->messageLogger->log(
                $account,
                $to,
                $type,
                $message,
                $payload,
                $response,
                WhatsAppMessage::STATUS_FAILED,
            );

            throw $e;
        }
    }

    protected function shouldQueue(): bool
    {
        return $this->queued && config('whatsapp.queue_enabled', true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function recordOutgoingConversation(
        WhatsAppAccount $account,
        string $to,
        string $type,
        ?string $message,
        array $payload,
        ?string $wamid,
    ): void {
        if (! config('whatsapp.conversations.enabled', true)) {
            return;
        }

        $this->conversationRecorder->recordOutgoing(
            $account,
            $to,
            $type,
            $message,
            $payload,
            $wamid,
        );
    }
}
