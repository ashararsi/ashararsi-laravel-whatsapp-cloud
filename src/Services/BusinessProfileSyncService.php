<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Database\Eloquent\Collection;
use Vendor\LaravelWhatsAppCloud\Events\BusinessProfileSynced;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppBusinessProfile;

class BusinessProfileSyncService
{
    public function __construct(
        protected GraphApiClient $graphApi,
    ) {}

    public function syncAccount(WhatsAppAccount $account): WhatsAppBusinessProfile
    {
        if (! $account->isMeta()) {
            throw new WhatsAppException('Business profile sync is only supported for Meta accounts.');
        }

        $this->ensureConfigured($account);

        $wabaId = (string) $account->waba_id;
        $phoneNumberId = (string) $account->phone_number_id;

        $waba = $this->graphApi->get(
            (string) $account->access_token,
            $wabaId,
            ['fields' => 'name,account_review_status,timezone_id'],
        );

        $phone = $this->graphApi->get(
            (string) $account->access_token,
            $phoneNumberId,
            ['fields' => 'display_phone_number,verified_name,quality_rating,messaging_limit_tier,status,name'],
        );

        $profile = $this->graphApi->get(
            (string) $account->access_token,
            "{$phoneNumberId}/whatsapp_business_profile",
            ['fields' => 'about,address,description,email,profile_picture_url,websites,vertical'],
        );

        $record = WhatsAppBusinessProfile::query()->updateOrCreate(
            ['account_id' => $account->id],
            [
                'business_name' => (string) ($waba['name'] ?? ''),
                'display_name' => (string) ($phone['verified_name'] ?? $phone['name'] ?? ''),
                'verification_status' => (string) ($waba['account_review_status'] ?? $phone['status'] ?? ''),
                'quality_rating' => (string) ($phone['quality_rating'] ?? ''),
                'messaging_tier' => (string) ($phone['messaging_limit_tier'] ?? ''),
                'meta_json' => [
                    'waba' => $waba,
                    'phone' => $phone,
                    'profile' => $profile['data'][0] ?? $profile,
                ],
                'synced_at' => now(),
            ],
        );

        event(new BusinessProfileSynced($account, $record));

        return $record;
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

    protected function ensureConfigured(WhatsAppAccount $account): void
    {
        if (! $account->waba_id || ! $account->phone_number_id || ! $account->access_token) {
            throw new WhatsAppException('Meta account requires waba_id, phone_number_id, and access_token.');
        }
    }
}
