<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

Artisan::command('school:create-admin', function () {
    $name = trim((string) $this->ask('Administrator name'));
    $email = trim((string) $this->ask('Administrator email'));
    $password = (string) $this->secret('Password (12 characters minimum)');
    $confirm = (string) $this->secret('Confirm password');

    $validator = Validator::make([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $confirm,
    ], [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'confirmed', Password::min(12)],
    ]);

    if ($validator->fails()) {
        $this->error($validator->errors()->first());
        return 1;
    }

    User::create([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'role' => 'admin',
    ]);

    $this->info('Administrator account created.');
    return 0;
})->purpose('Create a SchoolApp administrator through a secure terminal prompt');


// Backwards-compatible command for existing users.
Artisan::command('college:create-admin', function () {
    $this->warn('Use school:create-admin for new installations.');
    return $this->call('school:create-admin');
})->purpose('Legacy alias for school:create-admin');


// Read-only release inspection. Does not connect to the database or change files.
Artisan::command('school:release-check {--production : Require production-safe settings}', function () {
    $production = $this->option('production') || app()->environment('production');
    $checks = [
        'APP_KEY is configured' => filled(config('app.key')) && (string) config('app.key') !== 'base64:',
        'Vite asset manifest exists' => is_file(public_path('build/manifest.json')),
        'Storage directory exists' => is_dir(storage_path('framework')) && is_writable(storage_path('framework')),
    ];

    if ($production) {
        $url = (string) config('app.url');
        $checks += [
            'APP_ENV is production' => app()->environment('production'),
            'APP_DEBUG is disabled' => config('app.debug') === false,
            'APP_URL uses HTTPS' => str_starts_with(strtolower($url), 'https://'),
            'Legacy global routes are disabled' => config('school_security.legacy_global_routes_enabled') === false,
            'Secure session cookies are enabled' => config('session.secure') === true,
            'Session cookies are HTTP-only' => config('session.http_only') === true,
            'Database does not use the root account' => config('database.default') !== 'mysql'
                || !in_array(strtolower((string) config('database.connections.mysql.username')), ['root', ''], true),
        ];
    }

    foreach ($checks as $name => $passed) {
        $this->line(($passed ? 'PASS' : 'FAIL').'  '.$name);
    }

    $failed = count(array_filter($checks, static fn ($passed) => !$passed));
    if ($failed > 0) {
        $this->error($failed.' release checks failed. No configuration or data was changed.');
        return 1;
    }

    $this->info('Release configuration checks passed. This is not a production security approval.');
    return 0;
})->purpose('Read-only SchoolApp deployment preflight without exposing credentials');


// Only operators with command-line access can bootstrap website ownership.
// Never grant platform-owner access merely from a legacy global admin role.
Artisan::command('website:owner:grant {email}', function (string $email) {
    $user = \App\Models\User::where('email', $email)->whereNull('disabled_at')->first();
    if (! $user) {
        $this->error('No active existing account with that email.');
        return 1;
    }
    \App\Models\WebsiteOwner::firstOrCreate(['user_id' => $user->id]);
    $this->info('Website owner access granted to existing account.');
    return 0;
})->purpose('Provision website owner from a trusted terminal session only');
