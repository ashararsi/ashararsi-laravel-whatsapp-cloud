<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Support\Facades\Log;
use Vendor\LaravelWhatsAppCloud\Contracts\AccountResolverInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\ConversationRecorderInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\MessageLoggerInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\SupportsInteractiveMessages;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppProviderInterface;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Jobs\SendWhatsAppMessageJob;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTemplate;
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
        protected MediaUploadService $mediaUpload,
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
     * @param  array<int, array<string, mixed>>  $components
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

    /**
     * Send a template using simple body variables.
     *
     * WhatsApp::template('923001234567', 'order_confirmed', ['Ali', '#12345']);
     *
     * @param  array<int, string|int|float>  $variables
     */
    public function template(
        string $to,
        string $templateName,
        array $variables = [],
        ?string $language = null,
    ): WhatsAppMessage {
        $account = $this->accountResolver->resolve($this->accountIdentifier);
        $stored = $this->resolveStoredTemplate($account, $templateName, $language);

        $resolvedLanguage = $language ?? ($stored !== null ? $stored->language : null) ?? 'en_US';

        $components = WhatsAppMessageBuilder::templateComponentsFromVariables($variables);

        return $this->sendTemplate($to, $templateName, $resolvedLanguage, $components);
    }

    protected function resolveStoredTemplate(
        WhatsAppAccount $account,
        string $templateName,
        ?string $language = null,
    ): ?WhatsAppTemplate {
        return WhatsAppTemplate::query()
            ->where('account_id', $account->id)
            ->where('template_name', $templateName)
            ->when($language, fn ($q) => $q->where('language', $language))
            ->orderByDesc('synced_at')
            ->first();
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

    public function sendImageFile(string $to, string $filePath, ?string $caption = null): WhatsAppMessage
    {
        return $this->dispatchFile(
            'image_file',
            $to,
            $caption ?? basename($filePath),
            $filePath,
            'image',
            fn ($provider) => $provider->sendImageFile($to, $filePath, $caption),
        );
    }

    public function sendDocumentFile(
        string $to,
        string $filePath,
        ?string $filename = null,
        ?string $caption = null,
    ): WhatsAppMessage {
        $filename ??= basename($filePath);

        return $this->dispatchFile(
            'document_file',
            $to,
            $filename,
            $filePath,
            'document',
            fn ($provider) => $provider->sendDocumentFile($to, $filePath, $filename, $caption),
        );
    }

    public function sendFile(string $to, string $filePath, ?string $caption = null): WhatsAppMessage
    {
        $mime = is_file($filePath) ? (mime_content_type($filePath) ?: '') : '';
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $isImage = str_starts_with($mime, 'image/')
            || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);

        return $isImage
            ? $this->sendImageFile($to, $filePath, $caption)
            : $this->sendDocumentFile($to, $filePath, basename($filePath), $caption);
    }

    /**
     * @param  callable(WhatsAppProviderInterface): ProviderResult  $sender
     */
    protected function dispatchFile(
        string $queuedType,
        string $to,
        ?string $message,
        string $filePath,
        string $mediaKind,
        callable $sender,
    ): WhatsAppMessage {
        $account = $this->accountResolver->resolve($this->accountIdentifier);

        if ($this->shouldQueue()) {
            $mediaId = $this->mediaUpload->upload($account, $filePath);
            $provider = $this->providerFactory->resolve($account);

            $options = match ($mediaKind) {
                'image' => ['media_id' => $mediaId, 'caption' => $message !== basename($filePath) ? $message : null],
                default => [
                    'media_id' => $mediaId,
                    'filename' => $message,
                    'caption' => null,
                ],
            };

            $payload = $provider->buildPayload($queuedType, $to, $options);

            $log = $this->messageLogger->log(
                $account,
                $to,
                $mediaKind,
                $message,
                $payload,
                null,
                WhatsAppMessage::STATUS_PENDING,
            );

            $job = new SendWhatsAppMessageJob(
                accountId: $account->id,
                payload: $payload,
                type: $queuedType,
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

        return $this->sendNow($account, $mediaKind, $to, $message, $sender);
    }

    /**
     * @param  array<int, array<string, string>>  $buttons
     */
    public function sendButtons(
        string $to,
        string $body,
        array $buttons,
        ?string $header = null,
        ?string $footer = null,
    ): WhatsAppMessage {
        return $this->dispatch(
            'buttons',
            $to,
            $body,
            compact('body', 'buttons', 'header', 'footer'),
            function (SupportsInteractiveMessages $provider) use ($to, $body, $buttons, $header, $footer) {
                return $provider->sendButtons($to, $body, $buttons, $header, $footer);
            },
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    public function sendList(
        string $to,
        string $body,
        string $buttonText,
        array $sections,
        ?string $header = null,
        ?string $footer = null,
    ): WhatsAppMessage {
        return $this->dispatch(
            'list',
            $to,
            $body,
            compact('body', 'buttonText', 'sections', 'header', 'footer') + ['button_text' => $buttonText],
            function (SupportsInteractiveMessages $provider) use ($to, $body, $buttonText, $sections, $header, $footer) {
                return $provider->sendList($to, $body, $buttonText, $sections, $header, $footer);
            },
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
     * @param  callable(WhatsAppProviderInterface): ProviderResult  $sender
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

        if (in_array($type, ['buttons', 'list'], true) && ! $provider instanceof SupportsInteractiveMessages) {
            throw new WhatsAppException("Provider [{$account->provider}] does not support interactive messages.");
        }

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
     * @param  callable(WhatsAppProviderInterface): ProviderResult|null  $sender
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
            } elseif ($provider instanceof SupportsInteractiveMessages) {
                $result = $senderOrPayload($provider);
            } else {
                throw new WhatsAppException("Provider [{$account->provider}] does not support this message type.");
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
