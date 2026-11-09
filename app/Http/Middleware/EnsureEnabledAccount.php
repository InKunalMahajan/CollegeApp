<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnabledAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->disabled_at !== null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This account is disabled. Contact the website owner.',
            ]);
        }

        return $next($request);
    }
}
