# Phase 4 — School and Academic Management

This pull request adds **working CRUD code for administrator-managed institution setup** (subject to local integration and test verification).

## Scope
- Schools: name, unique code, optional email.
- Campuses: school, name, code unique within school, optional city.
- Academic years: school, name unique within school, start date and end date validation.
- Subjects: school, name, code unique within school.
- Class sections: campus, academic year, name, code unique per campus and year.
- Admin-only list, create, edit and delete pages for the five modules.
- Foreign keys restrict deletion of referenced schools, campuses and academic years.
- Class section creation/update validates that campus and academic year share the same school.
- Basic SQLite feature tests for admin access, creations, duplicate codes and school consistency.

## Security and scope limitations
- Only users with the existing global `admin` role may use `/setup/*`.
- There is **no campus-specific membership or per-school authorization yet**. These must be implemented before allowing separate school administrators to operate in a shared database.
- There are no teacher, enrollment, subject assignment, academic term or timetable workflows yet.
- Existing `departments`, `courses` and `students` tables have not been migrated to school/campus ownership; do not assume their data is tenant-separated.
- Avoid storing real student data until production access control, audit logging and data protection are implemented.

## Windows 11 commands

First commit or stash your local changes, then:

```powershell
git fetch origin
git switch feat/phase4-school-academic-management
php artisan optimize:clear
php artisan migrate
php artisan test
npm run build
php artisan serve
```

Log in as admin and visit `/setup/schools`, then add campuses, academic years, subjects and class sections in that order.

**Never run `migrate:fresh` on a database containing real records.** Back up MySQL first. The GitHub PR is draft until tested locally on Windows 11.
