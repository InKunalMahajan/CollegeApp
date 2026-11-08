# CollegeApp — College Management System

**Stage 1 implementation pack** for Laravel 13, Tailwind CSS v4, and MySQL 9.x. No starter kits.

> This repository currently holds the CollegeApp feature implementation, not the complete Laravel framework skeleton. Follow setup below to create the standard Laravel project and integrate these files. **Local development only:** auth and role permissions are not yet built. Do not deploy or enter real personal student data.

## Set up on Apple Silicon Mac

```bash
composer create-project laravel/laravel collegeapp-local "^13.0"
cd collegeapp-local
npm install
npm install -D tailwindcss @tailwindcss/vite
```

Copy the application-specific files from this repository onto the generated Laravel project, preserving their paths. Update `vite.config.js` to register `tailwindcss()` from `@tailwindcss/vite` alongside the existing Laravel plugin. In `resources/css/app.css`, use `@import "tailwindcss";`.

Create your MySQL database:
```sql
CREATE DATABASE collegeapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Update your local `.env` (never commit credentials):
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=collegeapp
DB_USERNAME=root
DB_PASSWORD=
```

Then:
```bash
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

Open http://127.0.0.1:8000.

## Stage 1
- Dashboard counts from real database queries
- Students: searchable list, create/edit/delete with validation
- Department and course tables for future academic structure
- Blade-based responsive UI using Tailwind
- No sample records or fake counts

## Planned Stage 2
- Authentication and authorization (admin, staff, teacher, student)
- Department/course management pages
- Academic years, divisions, enrollments and timetables
- Attendance, assessment and grades
- Tests, CI, audit logging and production security

**Warning:** Routes are intentionally unprotected for local prototyping. Add middleware and authorization policies before any external access.
