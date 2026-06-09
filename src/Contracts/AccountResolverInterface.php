<?php

namespace Vendor\LaravelWhatsAppCloud\Contracts;

use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

interface AccountResolverInterface
{
    public function resolve(int|string|null $identifier = null): WhatsAppAccount;
}
