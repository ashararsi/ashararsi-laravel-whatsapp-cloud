<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Vendor\LaravelWhatsAppCloud\Events\WorkflowExecuted;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAiWorkflow;

class WorkflowEngine
{
    public function __construct(
        protected OpenAiService $openAi,
    ) {}

    public function run(WhatsAppAiWorkflow $workflow, string $phone, string $incomingMessage): ?string
    {
        if (! $workflow->is_active) {
            return null;
        }

        $account = $workflow->account;
        $steps = $workflow->steps_json ?? [];
        $reply = null;

        if ($this->openAi->isConfigured()) {
            try {
                $prompt = ($workflow->system_prompt ?? 'You are a WhatsApp AI agent.')."\n\nIncoming: ".$incomingMessage;
                if ($steps !== []) {
                    $prompt .= "\n\nWorkflow steps: ".json_encode($steps);
                }
                $reply = trim($this->openAi->chat('Execute workflow and reply to customer.', $prompt));
            } catch (\Throwable $e) {
                report($e);
                $reply = (string) ($steps[0]['response'] ?? null);
            }
        } else {
            $reply = (string) ($steps[0]['response'] ?? null);
        }

        if ($reply) {
            WhatsApp::account($account->id)->sendText($phone, $reply);
            event(new WorkflowExecuted($workflow, $phone, $incomingMessage, $reply));
        }

        return $reply;
    }

    public function runActiveForAccount(WhatsAppAccount $account, string $phone, string $message): ?string
    {
        $workflow = WhatsAppAiWorkflow::query()
            ->where('account_id', $account->id)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        return $workflow ? $this->run($workflow, $phone, $message) : null;
    }
}
