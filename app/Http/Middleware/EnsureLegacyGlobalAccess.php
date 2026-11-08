<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLegacyGlobalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Fail closed in production unless an operator explicitly overrides.
        // This is a transitional safety guard, not an authorization policy.
        abort_unless(config('school_security.legacy_global_routes_enabled', false), 403);

        return $next($request);
    }
}
