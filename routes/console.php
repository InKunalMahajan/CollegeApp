<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

Artisan::command('college:create-admin', function () {
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
})->purpose('Create a CollegeApp administrator through a secure terminal prompt');
