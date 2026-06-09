<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeWhatsAppAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('whatsapp.admin.authorization_enabled', true)) {
            return $next($request);
        }

        $gate = config('whatsapp.admin.gate', 'manage-whatsapp');

        if (Gate::has($gate) && Gate::denies($gate)) {
            abort(403, 'Unauthorized access to WhatsApp admin.');
        }

        if (! Gate::has($gate) && ! app()->environment('local', 'testing')) {
            abort(403, 'WhatsApp admin gate ['.$gate.'] is not defined.');
        }

        return $next($request);
    }
}
