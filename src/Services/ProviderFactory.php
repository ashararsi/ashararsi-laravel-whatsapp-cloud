<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppProviderInterface;
use Vendor\LaravelWhatsAppCloud\Exceptions\WhatsAppException;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Providers\MetaProvider;
use Vendor\LaravelWhatsAppCloud\Providers\TwilioProvider;

class ProviderFactory
{
    public function __construct(
        protected WhatsAppClientInterface $metaClient,
    ) {}

    public static function make(WhatsAppAccount $account): WhatsAppProviderInterface
    {
        return app(self::class)->resolve($account);
    }

    public function resolve(WhatsAppAccount $account): WhatsAppProviderInterface
    {
        return match ($account->provider ?? WhatsAppAccount::PROVIDER_META) {
            WhatsAppAccount::PROVIDER_META => new MetaProvider($account, $this->metaClient),
            WhatsAppAccount::PROVIDER_TWILIO => new TwilioProvider($account),
            default => throw new WhatsAppException("Unsupported WhatsApp provider [{$account->provider}]."),
        };
    }
}
