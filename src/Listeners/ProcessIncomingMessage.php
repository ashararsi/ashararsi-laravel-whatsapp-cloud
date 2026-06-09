<?php

namespace Vendor\LaravelWhatsAppCloud\Listeners;

use Vendor\LaravelWhatsAppCloud\Events\MessageReceived;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversationMessage;
use Vendor\LaravelWhatsAppCloud\Services\AudioTranscriptionService;
use Vendor\LaravelWhatsAppCloud\Services\AutoReplyEngine;
use Vendor\LaravelWhatsAppCloud\Services\MediaDownloadService;
use Vendor\LaravelWhatsAppCloud\Services\WorkflowEngine;
use Vendor\LaravelWhatsAppCloud\Support\IncomingMessageParser;

class ProcessIncomingMessage
{
    public function __construct(
        protected MediaDownloadService $mediaDownload,
        protected AudioTranscriptionService $transcription,
        protected AutoReplyEngine $autoReply,
        protected WorkflowEngine $workflows,
    ) {}

    public function handle(MessageReceived $event): void
    {
        $parsed = IncomingMessageParser::parse($event->payload, []);
        $phone = $parsed['phone'] ?? null;
        $body = $parsed['body'] ?? '';

        if (! is_string($phone) || $phone === '') {
            return;
        }

        $conversationMessage = WhatsAppConversationMessage::query()
            ->where('whatsapp_message_id', $parsed['whatsapp_message_id'] ?? null)
            ->latest('id')
            ->first();

        $mediaFile = $this->mediaDownload->downloadFromIncomingMessage(
            $event->account,
            $event->payload,
            $conversationMessage,
        );

        if ($mediaFile) {
            $this->transcription->transcribeMediaFile($mediaFile);
        }

        $isFirst = $this->autoReply->isFirstIncomingMessage($event->account, $phone);

        if ($this->autoReply->handleIncoming($event->account, $phone, (string) $body, $isFirst)) {
            return;
        }

        $this->workflows->runActiveForAccount($event->account, $phone, (string) $body);
    }
}
