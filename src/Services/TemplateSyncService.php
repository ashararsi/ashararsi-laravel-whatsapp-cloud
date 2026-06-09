<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Database\Eloquent\Collection;
use Vendor\LaravelWhatsAppCloud\Events\TemplateSynced;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTemplate;

class TemplateSyncService
{
    public function __construct(
        protected GraphApiClient $graphApi,
    ) {}

    public function syncAccount(WhatsAppAccount $account): int
    {
        if (! $account->isMeta()) {
            throw new WhatsAppException('Template sync is only supported for Meta accounts.');
        }

        $wabaId = (string) ($account->waba_id ?? $account->phone_number_id);

        $response = $this->graphApi->get(
            (string) $account->access_token,
            "{$wabaId}/message_templates",
            ['limit' => 250],
        );

        $count = 0;

        foreach ($response['data'] ?? [] as $template) {
            if (! is_array($template)) {
                continue;
            }

            $templateName = (string) ($template['name'] ?? '');

            if ($templateName === '') {
                continue;
            }

            WhatsAppTemplate::query()->updateOrCreate(
                [
                    'account_id' => $account->id,
                    'template_name' => $templateName,
                    'language' => (string) ($template['language'] ?? 'en_US'),
                ],
                [
                    'provider' => $account->provider,
                    'category' => isset($template['category']) ? strtoupper((string) $template['category']) : null,
                    'status' => isset($template['status']) ? strtoupper((string) $template['status']) : null,
                    'components_json' => $template['components'] ?? [],
                    'meta_template_id' => isset($template['id']) ? (string) $template['id'] : null,
                    'synced_at' => now(),
                ],
            );

            $count++;
        }

        event(new TemplateSynced($account, $count));

        return $count;
    }

    /**
     * @return Collection<int, WhatsAppAccount>
     */
    public function resolveAccountsForSync(?string $account = null, ?string $provider = null)
    {
        return WhatsAppAccount::query()
            ->active()
            ->when($account, fn ($q) => $q->where('id', $account)->orWhere('name', $account))
            ->when($provider, fn ($q) => $q->where('provider', $provider))
            ->when(! $provider && ! $account, fn ($q) => $q->where('provider', WhatsAppAccount::PROVIDER_META))
            ->get();
    }
}
