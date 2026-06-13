<?php

namespace Vendor\LaravelWhatsAppCloud\Contracts;

interface TenantResolverInterface
{
    /**
     * Resolve the current tenant ID for the active request or context.
     *
     * Return null when no tenant applies (e.g. system routes).
     */
    public function resolve(): ?int;
}
