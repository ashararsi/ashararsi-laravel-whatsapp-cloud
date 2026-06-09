<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Vendor\LaravelWhatsAppCloud\Contracts\ConversationRecorderInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\MessageLoggerInterface;
use Vendor\LaravelWhatsAppCloud\Events\MessageDelivered;
use Vendor\LaravelWhatsAppCloud\Events\MessageReceived;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Support\TwilioIncomingMessageParser;

class TwilioWebhookHandler
{
    public function __construct(
        protected TwilioSignatureValidator $signatureValidator,
        protected ConversationRecorderInterface $conversationRecorder,
        protected MessageLoggerInterface $messageLogger,
    ) {}

    public function handleInbound(Request $request): Response
    {
        $payload = $request->all();
        $account = $this->resolveAccount($payload);

        if (! $account) {
            abort(404, 'Twilio account not found.');
        }

        if (! $this->signatureValidator->isValid($request, (string) $account->twilio_token)) {
            abort(403, 'Invalid Twilio signature.');
        }

        $parsed = TwilioIncomingMessageParser::parse($payload);

        if (config('whatsapp.log_messages', true)) {
            $this->messageLogger->logIncoming(
                $account,
                $parsed['phone'],
                $parsed['type'],
                $parsed['body'],
                $parsed['payload'],
                $parsed['whatsapp_message_id'],
            );
        }

        if (config('whatsapp.conversations.enabled', true)) {
            $this->conversationRecorder->recordIncoming(
                $account,
                $this->toMetaShape($parsed),
                [],
            );
        }

        event(new MessageReceived($account, $parsed['payload']));

        return response('', 200)->header('Content-Type', 'text/plain');
    }

    public function handleStatus(Request $request): Response
    {
        $payload = $request->all();
        $account = $this->resolveAccount($payload);

        if (! $account) {
            abort(404, 'Twilio account not found.');
        }

        if (! $this->signatureValidator->isValid($request, (string) $account->twilio_token)) {
            abort(403, 'Invalid Twilio signature.');
        }

        $sid = (string) ($payload['MessageSid'] ?? '');
        $status = strtolower((string) ($payload['MessageStatus'] ?? ''));

        if ($sid === '' || $status === '') {
            return response('', 200);
        }

        $mapped = match ($status) {
            'queued' => WhatsAppMessage::STATUS_PENDING,
            'sent' => WhatsAppMessage::STATUS_SENT,
            'delivered' => WhatsAppMessage::STATUS_DELIVERED,
            'failed', 'undelivered' => WhatsAppMessage::STATUS_FAILED,
            default => null,
        };

        if ($mapped) {
            $message = WhatsAppMessage::query()
                ->where('account_id', $account->id)
                ->where('whatsapp_message_id', $sid)
                ->first();

            if ($message && $this->shouldAdvanceStatus($message->status, $mapped)) {
                $message->update(['status' => $mapped]);

                if ($mapped === WhatsAppMessage::STATUS_DELIVERED) {
                    event(new MessageDelivered($account, $payload));
                }
            }
        }

        return response('', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveAccount(array $payload): ?WhatsAppAccount
    {
        $sid = $payload['AccountSid'] ?? null;

        if (! is_string($sid) || $sid === '') {
            return null;
        }

        return WhatsAppAccount::query()
            ->where('provider', WhatsAppAccount::PROVIDER_TWILIO)
            ->where('twilio_sid', $sid)
            ->active()
            ->first();
    }

    /**
     * @param  array{phone: string, type: string, body: ?string, whatsapp_message_id: ?string, payload: array<string, mixed>}  $parsed
     * @return array<string, mixed>
     */
    protected function toMetaShape(array $parsed): array
    {
        return [
            'from' => $parsed['phone'],
            'id' => $parsed['whatsapp_message_id'],
            'type' => $parsed['type'],
            'text' => ['body' => $parsed['body']],
        ];
    }

    protected function shouldAdvanceStatus(string $current, string $incoming): bool
    {
        if ($current === $incoming) {
            return false;
        }

        $rank = [
            WhatsAppMessage::STATUS_PENDING => 0,
            WhatsAppMessage::STATUS_SENT => 1,
            WhatsAppMessage::STATUS_DELIVERED => 2,
            WhatsAppMessage::STATUS_FAILED => 99,
        ];

        if ($incoming === WhatsAppMessage::STATUS_FAILED) {
            return true;
        }

        return ($rank[$incoming] ?? 0) > ($rank[$current] ?? 0);
    }
}
