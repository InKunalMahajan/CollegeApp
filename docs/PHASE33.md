# Phase 33 — Safe CRUD for Employees and Departments

## Scope
Completes missing CRUD operations in the two school-scoped modules. Other SchoolApp modules are outside this PR and must not be represented as fully CRUD-complete.

## Employees
- Create: school administrator; existing validated POST endpoint.
- Read: authorized school/department directory and profile.
- Update: school administrator; existing validated PUT endpoint.
- Archive: school administrator DELETE; Laravel SoftDeletes retains records, unique employee number, and audit.
- Restore: school administrator POST; archived record returns to directory.
- An employee who has active direct reportees cannot be archived until reportees are reassigned.
- The OrgTree CSV import rejects an archived employee number until an admin restores it.

## Departments
- Create: existing school administrator POST endpoint.
- Read: existing authorized school/department list and detail pages.
- Update: administrator PUT with uppercase unique code, name and description.
- Delete: administrator DELETE only for empty departments with no employee references (including archived) and no department memberships.
- Audit records capture creation, updates and deletion.

## Security
- Server-side Phase 30 school.can middleware and same-school ownership checks protect all new operations.
- A global admin label does not grant access to another school.
- Employees use soft deletion; no permanent employee deletion API.
- Departments only delete when unreferenced; schema foreign keys also protect integrity.

## Installation (PowerShell)
Back up the existing CollegeApp MySQL database before applying the migration.
```powershell
cd C:\Users\LENOVO\Documents\GitHub\CollegeApp
git status
git fetch origin
git switch --track origin/feat/phase33-safe-employee-department-crud
php artisan optimize:clear
php artisan migrate:status
php artisan migrate
php artisan test --filter=Phase33SafeCrudTest
php artisan test
npm run build
php artisan serve
```
If already on the feature branch, run git pull --ff-only instead of git switch --track.
Do not run migrate:fresh on a database containing real or retained school records.

## Manual QA
1. Create, inspect, update, archive and restore an employee as school administrator.
2. Verify a manager with direct reportees cannot be archived.
3. Try CSV import with an archived employee number; restore first.
4. Edit department title, code, description.
5. Verify deletion of used departments is rejected and empty departments can be deleted.
6. Confirm auditors, department members and outsiders have no write access.

## Boundaries
Not a broad delete feature for grades, fees or attendance. Student/academic/finance modules need separate lifecycle and retention design before full CRUD.