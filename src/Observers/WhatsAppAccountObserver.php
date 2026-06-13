<?php

namespace Vendor\LaravelWhatsAppCloud\Observers;

use Illuminate\Support\Facades\Cache;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

class WhatsAppAccountObserver
{
    public function saved(WhatsAppAccount $account): void
    {
        $this->flushCache($account);
    }

    public function deleted(WhatsAppAccount $account): void
    {
        $this->flushCache($account);

        if ($account->is_default) {
            $replacement = WhatsAppAccount::query()
                ->where('id', '!=', $account->id)
                ->when(
                    $account->tenant_id !== null,
                    fn ($query) => $query->where('tenant_id', $account->tenant_id),
                )
                ->active()
                ->oldest('id')
                ->first();

            if ($replacement) {
                WhatsAppAccount::setDefault($replacement);
            }
        }
    }

    protected function flushCache(WhatsAppAccount $account): void
    {
        Cache::forget('whatsapp.accounts.default');
        Cache::forget('whatsapp.accounts.first_active');
        Cache::forget("whatsapp.accounts.id.{$account->id}");
        Cache::forget("whatsapp.accounts.name.{$account->name}");
        Cache::forget("whatsapp.accounts.phone_number_id.{$account->phone_number_id}");
    }
}
