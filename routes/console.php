<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

Artisan::command('college:create-admin', function () {
    $name = trim((string) $this->ask('Administrator name'));
    $email = trim((string) $this->ask('Administrator email'));
    $password = (string) $this->secret('Password (at least 12 characters)');
    $confirmation = (string) $this->secret('Confirm password');

    if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($password) < 12 || $password !== $confirmation) {
        $this->error('Invalid input, short password, or passwords do not match.');
        return 1;
    }
    if (User::where('email', $email)->exists()) {
        $this->error('An account with this email already exists.');
        return 1;
    }

    User::create(['name' => $name, 'email' => $email, 'password' => $password, 'role' => 'admin']);
    $this->info('Administrator account created. No credentials were stored in the repository.');
    return 0;
})->purpose('Securely create the first CollegeApp administrator');
