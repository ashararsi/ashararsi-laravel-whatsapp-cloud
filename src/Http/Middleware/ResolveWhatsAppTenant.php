<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vendor\LaravelWhatsAppCloud\Services\TenantContext;

class ResolveWhatsAppTenant
{
    public function __construct(
        protected TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenantContext->resolveFromContainer();

        return $next($request);
    }
}
