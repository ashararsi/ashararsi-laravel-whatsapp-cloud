<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Database\Eloquent\Collection;
use Vendor\LaravelWhatsAppCloud\Events\PhoneNumbersSynced;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppSyncedPhoneNumber;

class PhoneNumberSyncService
{
    public function __construct(
        protected GraphApiClient $graphApi,
    ) {}

    public function syncAccount(WhatsAppAccount $account): int
    {
        if (! $account->isMeta()) {
            throw new WhatsAppException('Phone number sync is only supported for Meta accounts.');
        }

        if (! $account->waba_id || ! $account->access_token) {
            throw new WhatsAppException('Meta account requires waba_id and access_token.');
        }

        $response = $this->graphApi->get(
            (string) $account->access_token,
            "{$account->waba_id}/phone_numbers",
            ['fields' => 'id,display_phone_number,verified_name,status,quality_rating,messaging_limit_tier,name'],
        );

        $count = 0;

        foreach ($response['data'] ?? [] as $phone) {
            if (! is_array($phone)) {
                continue;
            }

            $phoneNumberId = (string) ($phone['id'] ?? '');

            if ($phoneNumberId === '') {
                continue;
            }

            WhatsAppSyncedPhoneNumber::query()->updateOrCreate(
                [
                    'account_id' => $account->id,
                    'phone_number_id' => $phoneNumberId,
                ],
                [
                    'display_phone_number' => (string) ($phone['display_phone_number'] ?? ''),
                    'verified_name' => (string) ($phone['verified_name'] ?? $phone['name'] ?? ''),
                    'status' => (string) ($phone['status'] ?? ''),
                    'quality_rating' => (string) ($phone['quality_rating'] ?? ''),
                    'messaging_tier' => (string) ($phone['messaging_limit_tier'] ?? ''),
                    'meta_json' => $phone,
                    'synced_at' => now(),
                ],
            );

            $count++;
        }

        event(new PhoneNumbersSynced($account, $count));

        return $count;
    }

    /**
     * @return Collection<int, WhatsAppAccount>
     */
    public function resolveAccountsForSync(?string $account = null)
    {
        return WhatsAppAccount::query()
            ->active()
            ->where('provider', WhatsAppAccount::PROVIDER_META)
            ->when($account, fn ($q) => $q->where('id', $account)->orWhere('name', $account))
            ->get();
    }
}
