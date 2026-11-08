# SchoolApp — School Management System

SchoolApp is a Laravel 13 + Tailwind CSS v4 + MySQL school administration application.

## Current stage
Role-based login (admin, staff, teacher, student), a responsive dashboard, basic student management, administrator-managed user accounts, an overview of 16 planned modules, and initial setup for schools, campuses, academic years, subjects and class sections.

**Status:** Development prototype. Some modules are partial; other modules remain planned. Run tests and a security review before storing real student records.

## Windows 11

```powershell
git fetch origin
git switch feat/rename-college-to-school
composer install
npm install
php artisan optimize:clear
php artisan migrate
php artisan test
npm run build
php artisan school:create-admin
php artisan serve
```

Start at http://127.0.0.1:8000/login.

Set local MySQL credentials in an untracked `.env` file. Keep your existing `DB_DATABASE` value if the database is already in use. Do not share or commit passwords.

## Compatibility
- GitHub repository remains `InKunalMahajan/CollegeApp` so the current Git remote works.
- Display branding changes to **SchoolApp** and **School Management System**.
- `APP_NAME` defaults to `SchoolApp` in `.env.example`; existing local `.env` files are not modified.
- Existing database identifiers, including `college_modules` config, `courses`, `departments`, and prior migrations, remain unchanged to protect compatibility.
- The old `college:create-admin` CLI command remains as an alias. Use `school:create-admin` for new setup.

See `docs/MODULE_ROADMAP.md`, `docs/PHASE4.md` and `docs/SECURITY.md`.
