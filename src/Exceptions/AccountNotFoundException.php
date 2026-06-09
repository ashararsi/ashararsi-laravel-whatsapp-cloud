<?php

namespace Vendor\LaravelWhatsAppCloud\Exceptions;

class AccountNotFoundException extends WhatsAppException
{
    public function __construct(string $identifier)
    {
        parent::__construct("WhatsApp account [{$identifier}] not found.");
    }
}
