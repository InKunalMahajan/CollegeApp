# SchoolApp — Windows 11 setup and test fixes

## Current GitHub status

This branch contains the SchoolApp-specific Laravel code. It is **not yet a complete installable Laravel framework skeleton**. For a fully working copy, generate a Laravel 13 application first and integrate these files. Do not commit `.env`, `vendor/` or `node_modules/`.

## Install on Windows 11 (PowerShell)

```powershell
cd C:\Users\LENOVO\Documents\GitHub
composer create-project laravel/laravel SchoolAppLaravel "^13.0"
```

When integrating into an existing `SchoolApp` repo, preserve the SchoolApp controllers, models, routes, migrations and Blade files. Copy **missing Laravel skeleton files** from `SchoolAppLaravel`, and never overwrite existing code or secrets blindly.

Check:

```powershell
cd C:\Users\LENOVO\Documents\GitHub\SchoolApp
Test-Path artisan
Test-Path composer.json
Test-Path package.json
composer install
npm install
npm install -D tailwindcss @tailwindcss/vite
```

Ensure `vite.config.js` registers `tailwindcss()` from `@tailwindcss/vite` and `resources/css/app.css` imports Tailwind CSS.

Set MySQL credentials in the private `.env` file; then:

```powershell
php artisan key:generate
php artisan migrate
php artisan route:list
php artisan school:create-admin
npm run build
php artisan serve
```

## Tests

```powershell
php -l tests\Feature\ExampleTest.php
php artisan optimize:clear
php artisan test
```

The homepage is protected by authentication. A guest request to `/` should redirect to `/login` (302), not return 200. `tests/Feature/ExampleTest.php` now asserts the redirect.

**Do not paste PowerShell here-string delimiters (`@'` and `'@`) into PHP source code.** Save files as UTF-8 without BOM, with `<?php` as the first bytes.

## SQLite test database and role column

Tests use an SQLite in-memory database when configured in `phpunit.xml`; your application can still use MySQL. If tests report `table users has no column named role`, check your **effective local migrations**:

- This repository's Stage 2 `database/migrations/0001_01_01_000000_create_users_table.php` already includes the `role` column.
- If your existing Laravel migration already ran **without** `role`, create a **separate additive migration** to add it to the live database.
- Do **not** run both a schema-creation migration with `role` and another migration that adds `role`, or you will get duplicate-column errors.
- Do **not** run `migrate:fresh` on a database with real data.

## Security

This is a development prototype. Test role authorization, account provisioning, credential recovery, and database backups before handling actual student data.
