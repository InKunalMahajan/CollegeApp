# SchoolApp — Phase 5 Teacher Management

## Added
- Teacher profiles linked to existing login accounts with role `teacher`, a school and a campus
- School-specific employee code, designation, optional joining date
- Admin-only profile create/edit/list and teaching-assignment management
- Subject assignments by subject/class/teacher
- Class-teacher allocation: one class teacher per section
- Teacher-only `/my-teaching` page listing **only assignments for the authenticated teacher**
- Validation for correct campus and school on assignments
- Database uniqueness and foreign-key constraints; authorization feature tests

## Roles
- Admin: create/edit profiles and assign classes/subjects
- Teacher: read own teaching assignments; cannot edit profile or manage others
- Staff and Student: cannot access teacher management routes

## Limitations
- No per-school administrator roles or school-specific tenant permissions. Global admin has access to all school profiles.
- No deletion of teacher profiles in UI. Remove assignments through the assignment page before reassigning a teacher to another campus.
- No attendance, grades, timetables or student rosters on teacher dashboard yet.
- Teacher account creation remains within the admin User accounts module.
- Existing MySQL `users.role` column may be present already even when its migration is marked Pending. Reconcile **migration history** on your current database before applying Phase 5; do not delete or recreate users.

## Windows 11 validation
```powershell
git fetch origin
git switch feat/phase5-teacher-management
php artisan migrate:status
php artisan migrate
php artisan test --filter=TeacherManagementTest
php artisan test
npm run build
php artisan serve
```
First back up your database. Do not use `migrate:fresh` with personal data. If the pending `role` migration would create a duplicate column, stop and reconcile it before running `migrate`.

Start with admin at `/users/create` (choose Teacher), then `/setup/schools`, `/teachers`, `/teacher-assignments`. Sign in as that teacher to check `/my-teaching`.

**Not production ready:** Review access policies, audit logs, teacher/student privacy, credential management, and multi-school isolation before deploying.
