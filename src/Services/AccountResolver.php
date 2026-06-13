<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Support\Facades\Cache;
use Vendor\LaravelWhatsAppCloud\Contracts\AccountResolverInterface;
use Vendor\LaravelWhatsAppCloud\Exceptions\AccountNotFoundException;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Services\TenantContext;

class AccountResolver implements AccountResolverInterface
{
    public function __construct(
        protected TenantContext $tenantContext,
    ) {}
    public function resolve(int|string|null $identifier = null): WhatsAppAccount
    {
        if ($identifier !== null) {
            return $this->resolveByIdentifier($identifier);
        }

        $configDefault = config('whatsapp.default_account');

        if ($configDefault !== null && $configDefault !== '') {
            return $this->resolveByIdentifier($configDefault);
        }

        $accountId = $this->rememberId('whatsapp.accounts.default'.$this->tenantCacheSuffix(), function () {
            return $this->scopedAccountQuery()
                ->active()
                ->default()
                ->value('id');
        });

        if ($accountId) {
            return $this->findAccountOrFail((int) $accountId, 'default');
        }

        $accountId = $this->rememberId('whatsapp.accounts.first_active'.$this->tenantCacheSuffix(), function () {
            return $this->scopedAccountQuery()->active()->oldest('id')->value('id');
        });

        if ($accountId) {
            return $this->findAccountOrFail((int) $accountId, 'default');
        }

        throw new AccountNotFoundException('default');
    }

    public function findByPhoneNumberId(string $phoneNumberId): ?WhatsAppAccount
    {
        $accountId = $this->rememberId("whatsapp.accounts.phone_number_id.{$phoneNumberId}", function () use ($phoneNumberId) {
            return WhatsAppAccount::query()
                ->where('phone_number_id', $phoneNumberId)
                ->active()
                ->value('id');
        });

        if (! $accountId) {
            return null;
        }

        return WhatsAppAccount::query()->find($accountId);
    }

    protected function resolveByIdentifier(int|string $identifier): WhatsAppAccount
    {
        if (is_numeric($identifier)) {
            $cacheKey = "whatsapp.accounts.id.{$identifier}{$this->tenantCacheSuffix()}";

            $accountId = $this->rememberId($cacheKey, function () use ($identifier) {
                return $this->scopedAccountQuery()
                    ->active()
                    ->where('id', (int) $identifier)
                    ->value('id');
            });
        } else {
            $cacheKey = 'whatsapp.accounts.name.'.md5((string) $identifier).$this->tenantCacheSuffix();

            $accountId = $this->rememberId($cacheKey, function () use ($identifier) {
                return $this->scopedAccountQuery()
                    ->active()
                    ->where('name', (string) $identifier)
                    ->value('id');
            });
        }

        if (! $accountId) {
            throw new AccountNotFoundException((string) $identifier);
        }

        return $this->findAccountOrFail((int) $accountId, (string) $identifier);
    }

    protected function findAccountOrFail(int $accountId, string $identifier): WhatsAppAccount
    {
        $account = $this->scopedAccountQuery()->active()->find($accountId);

        if (! $account) {
            throw new AccountNotFoundException($identifier);
        }

        return $account;
    }

    protected function scopedAccountQuery()
    {
        return WhatsAppAccount::query();
    }

    protected function tenantCacheSuffix(): string
    {
        if (! $this->tenantContext->shouldScope()) {
            return '';
        }

        return '.tenant.'.$this->tenantContext->id();
    }

    protected function rememberId(string $key, callable $callback): mixed
    {
        if (! config('whatsapp.cache.enabled', true)) {
            return $callback();
        }

        $ttl = (int) config('whatsapp.cache.ttl', 300);

        return Cache::remember($key, $ttl, $callback);
    }
}
