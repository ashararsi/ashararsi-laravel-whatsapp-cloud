<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Vendor\LaravelWhatsAppCloud\Contracts\ConversationRecorderInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\MessageLoggerInterface;
use Vendor\LaravelWhatsAppCloud\Events\MessageDelivered;
use Vendor\LaravelWhatsAppCloud\Events\MessageRead;
use Vendor\LaravelWhatsAppCloud\Events\MessageReceived;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppMessage;
use Vendor\LaravelWhatsAppCloud\Support\IncomingMessageParser;

class WebhookHandler
{
    public function __construct(
        protected WebhookSignatureValidator $signatureValidator,
        protected ConversationRecorderInterface $conversationRecorder,
        protected MessageLoggerInterface $messageLogger,
    ) {}

    public function verify(Request $request): Response|string
    {
        $mode = $request->input('hub.mode', $request->input('hub_mode'));
        $token = $request->input('hub.verify_token', $request->input('hub_verify_token'));
        $challenge = $request->input('hub.challenge', $request->input('hub_challenge'));

        if ($mode !== 'subscribe' || ! is_string($challenge) || $challenge === '') {
            abort(403, 'Invalid verification request.');
        }

        $account = $this->resolveAccountByVerifyToken(is_string($token) ? $token : null);

        if (! $account) {
            abort(403, 'Invalid verify token.');
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function handle(Request $request): Response
    {
        $payload = $request->json()->all();
        $account = $this->resolveAccountFromMetaPayload($payload);

        if (! $this->signatureValidator->isValid($request, $account)) {
            abort(403, 'Invalid webhook signature.');
        }

        if ($payload === [] || ! isset($payload['entry']) || ! is_array($payload['entry'])) {
            return response('EVENT_RECEIVED', 200);
        }

        foreach ($payload['entry'] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            foreach ($entry['changes'] ?? [] as $change) {
                if (! is_array($change)) {
                    continue;
                }

                $value = $change['value'] ?? [];

                if (! is_array($value)) {
                    continue;
                }

                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

                if (! is_string($phoneNumberId) || $phoneNumberId === '') {
                    continue;
                }

                $resolvedAccount = $this->resolveAccountByPhoneNumberId($phoneNumberId) ?? $account;

                if (! $resolvedAccount) {
                    continue;
                }

                $this->handleMessages($resolvedAccount, $value);
                $this->handleStatuses($resolvedAccount, $value);
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    protected function handleMessages(WhatsAppAccount $account, array $value): void
    {
        $messages = $value['messages'] ?? [];

        if (! is_array($messages)) {
            return;
        }

        $contacts = $value['contacts'] ?? [];

        foreach ($messages as $message) {
            if (! is_array($message)) {
                continue;
            }

            $parsed = IncomingMessageParser::parse($message, is_array($contacts) ? $contacts : []);

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
                    $message,
                    is_array($contacts) ? $contacts : [],
                );
            }

            event(new MessageReceived($account, $message));
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    protected function handleStatuses(WhatsAppAccount $account, array $value): void
    {
        $statuses = $value['statuses'] ?? [];

        if (! is_array($statuses)) {
            return;
        }

        foreach ($statuses as $status) {
            if (! is_array($status)) {
                continue;
            }

            $statusType = $status['status'] ?? null;
            $wamid = $status['id'] ?? null;
            $recipient = $status['recipient_id'] ?? null;

            $updated = $this->updateMessageStatus(
                $account,
                is_string($wamid) ? $wamid : null,
                is_string($recipient) ? $recipient : null,
                is_string($statusType) ? $statusType : null,
            );

            if (! $updated) {
                continue;
            }

            match ($statusType) {
                'delivered' => event(new MessageDelivered($account, $status)),
                'read' => event(new MessageRead($account, $status)),
                default => null,
            };
        }
    }

    protected function updateMessageStatus(
        WhatsAppAccount $account,
        ?string $wamid,
        ?string $recipient,
        ?string $statusType,
    ): bool {
        if (! $statusType || ! config('whatsapp.log_messages', true)) {
            return false;
        }

        $status = match ($statusType) {
            'delivered' => WhatsAppMessage::STATUS_DELIVERED,
            'read' => WhatsAppMessage::STATUS_READ,
            'sent' => WhatsAppMessage::STATUS_SENT,
            'failed' => WhatsAppMessage::STATUS_FAILED,
            default => null,
        };

        if (! $status) {
            return false;
        }

        $query = WhatsAppMessage::query()->where('account_id', $account->id);

        if ($wamid) {
            $query->where('whatsapp_message_id', $wamid);
        } elseif ($recipient) {
            $query->where('to', $recipient)->latest('id');
        } else {
            return false;
        }

        $message = $query->first();

        if (! $message) {
            return false;
        }

        if (! $this->shouldAdvanceStatus($message->status, $status)) {
            return false;
        }

        $message->update(['status' => $status]);

        return true;
    }

    protected function shouldAdvanceStatus(string $current, string $incoming): bool
    {
        if ($current === $incoming) {
            return false;
        }

        $rank = [
            WhatsAppMessage::STATUS_PENDING => 0,
            WhatsAppMessage::STATUS_RECEIVED => 0,
            WhatsAppMessage::STATUS_SENT => 1,
            WhatsAppMessage::STATUS_DELIVERED => 2,
            WhatsAppMessage::STATUS_READ => 3,
            WhatsAppMessage::STATUS_FAILED => 99,
        ];

        if ($incoming === WhatsAppMessage::STATUS_FAILED) {
            return true;
        }

        return ($rank[$incoming] ?? 0) > ($rank[$current] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveAccountFromMetaPayload(array $payload): ?WhatsAppAccount
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            foreach ($entry['changes'] ?? [] as $change) {
                $phoneNumberId = $change['value']['metadata']['phone_number_id'] ?? null;

                if (is_string($phoneNumberId) && $phoneNumberId !== '') {
                    $account = $this->resolveAccountByPhoneNumberId($phoneNumberId);

                    if ($account) {
                        return $account;
                    }
                }
            }
        }

        return null;
    }

    protected function resolveAccountByVerifyToken(?string $token): ?WhatsAppAccount
    {
        if (! $token) {
            return null;
        }

        return WhatsAppAccount::query()
            ->where('webhook_verify_token', $token)
            ->active()
            ->first();
    }

    protected function resolveAccountByPhoneNumberId(string $phoneNumberId): ?WhatsAppAccount
    {
        return app(AccountResolver::class)->findByPhoneNumberId($phoneNumberId);
    }
}
