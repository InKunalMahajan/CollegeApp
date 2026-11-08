# Run Stage 1 + Stage 2 on Apple Silicon macOS

The feature branches contain CollegeApp-specific app files. They are **not yet** the complete Laravel distribution.

1. Install PHP 8.3+, Composer, Node.js and MySQL.
2. Generate a complete fresh Laravel 13 base:
   ```bash
   composer create-project laravel/laravel collegeapp-local "^13.0"
   cd collegeapp-local
   npm install
   npm install -D tailwindcss @tailwindcss/vite
   ```
3. Copy the Stage 1 + Stage 2 branch application files over the Laravel project's equivalent paths (models, controllers, middleware, migrations, routes, resources, bootstrap/app.php). Update `vite.config.js` with the `@tailwindcss/vite` plugin.
4. In MySQL:
   ```sql
   CREATE DATABASE collegeapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
5. Set `DB_CONNECTION=mysql`, host, port, database, user and password in `.env`. Do not commit credentials.
6. Run:
   ```bash
   php artisan key:generate
   php artisan migrate
   php artisan college:create-admin
   php artisan test
   npm run build
   php artisan serve
   ```
7. Sign in at http://127.0.0.1:8000/login.

**Migration note:** if a local project already has Laravel's default `0001_01_01_000000_create_users_table.php`, replace it with the CollegeApp migration *before the first migration*. If the users table was already migrated, create a separate additive migration for the `role` column rather than editing a previously-run migration.

**Status:** instructions have not been executed against a Mac; integration testing is still required.
