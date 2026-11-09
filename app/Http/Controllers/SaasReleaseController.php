<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class SaasReleaseController extends Controller
{
    public function index(): View
    {
        $tables = [
            'saas_tenants', 'saas_onboarding_requests', 'saas_plans',
            'saas_subscriptions', 'saas_invoices', 'saas_brandings', 'saas_domains',
        ];
        $schema = collect($tables)->mapWithKeys(
            fn ($table) => [$table => Schema::hasTable($table)]
        )->all();

        $checks = [
            'Application key exists' => filled(config('app.key')),
            'Debug mode disabled' => config('app.debug') === false,
            'Application URL uses HTTPS' => str_starts_with(strtolower((string) config('app.url')), 'https://'),
            'Legacy global routes disabled' => config('school_security.legacy_global_routes_enabled') === false,
            'Session cookies HTTPS only' => config('session.secure') === true,
            'Session cookies HTTP-only' => config('session.http_only') === true,
            'Compiled Vite manifest exists' => is_file(public_path('build/manifest.json')),
            'Database user is non-root' => config('database.default') !== 'mysql'
                || !in_array(strtolower((string)config('database.connections.mysql.username')), ['root',''],true),
        ];

        return view('saas.release', compact('checks', 'schema'));
    }
}
