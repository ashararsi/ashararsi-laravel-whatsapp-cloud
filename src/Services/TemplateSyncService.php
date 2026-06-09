<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Support\Facades\Http;
use Vendor\LaravelWhatsAppCloud\Events\TemplateSynced;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTemplate;

class TemplateSyncService
{
    public function syncAccount(WhatsAppAccount $account): int
    {
        if (! $account->isMeta()) {
            throw new WhatsAppException('Template sync is only supported for Meta accounts.');
        }

        $version = config('whatsapp.api_version', 'v21.0');
        $base = rtrim((string) config('whatsapp.api_base_url', 'https://graph.facebook.com'), '/');
        $wabaId = (string) ($account->waba_id ?? $account->phone_number_id);

        $response = Http::withToken((string) $account->access_token)
            ->get("{$base}/{$version}/{$wabaId}/message_templates", [
                'limit' => 250,
            ]);

        if (! $response->successful()) {
            throw new WhatsAppException('Failed to sync templates from Meta.', $response->json() ?? []);
        }

        $count = 0;

        foreach ($response->json('data', []) as $template) {
            if (! is_array($template)) {
                continue;
            }

            WhatsAppTemplate::query()->updateOrCreate(
                [
                    'account_id' => $account->id,
                    'name' => (string) ($template['name'] ?? ''),
                    'language' => (string) ($template['language'] ?? 'en_US'),
                ],
                [
                    'category' => $template['category'] ?? null,
                    'status' => $template['status'] ?? null,
                    'components_json' => $template['components'] ?? [],
                    'synced_at' => now(),
                ],
            );

            $count++;
        }

        event(new TemplateSynced($account, $count));

        return $count;
    }
}
