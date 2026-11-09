# Phase 41 — TEACH campus release and security gate

## Change inventory
- Phase 37: same-campus teacher subject/class assignment (no user provisioning).
- Phase 38: same-campus student and staff attendance with audited updates.
- Phase 39: same-campus exams and grade entry with maximum mark enforcement.
- Phase 40: **read-only** student fee charge, payment and library loan ledgers.
- Phase 41: cross-module, cross-campus, unrelated-organization and global-role-bypass regression tests.

## Security invariants
1. Organization admin: TEACH campuses only, never another organization's records.
2. Campus admin: assigned campus only; no implicit access from `users.role`.
3. A route's campus must belong to the route's school; mismatches return 404.
4. Student + class + academic year must independently match school/campus on every new write.
5. Teachers must belong to both the requested school and campus; subjects must belong to the school.
6. Financial and library historical records remain intact, and no campus-facing mutation route for money or inventory is introduced.
7. Legacy/unassigned student and employee data is excluded until explicitly verified and assigned.
8. Changes should be tested with two organizations, at least two TEACH campuses, cross-campus IDs, inactive/unassigned student IDs, duplicate submissions, and revoked membership.

## Release instructions
Back up the existing MySQL database; do not run `migrate:fresh` against real data.

```powershell
git fetch origin
git switch --track origin/feat/phase41-campus-release-hardening
php artisan optimize:clear
php artisan migrate:status
php artisan test --filter=Phase37CampusTeachingTest
php artisan test --filter=Phase38CampusAttendanceTest
php artisan test --filter=Phase39CampusExamsTest
php artisan test --filter=Phase40CampusFinanceLibraryTest
php artisan test --filter=Phase41CampusReleaseGateTest
php artisan test
npm run build
php artisan route:list --path=schools
php artisan serve
```

If the branch exists locally: `git switch feat/phase41-campus-release-hardening` and `git pull --ff-only`.

## Manual browser QA
- TEACH organization admin: Mumbai and Pune workspace navigation works.
- Mumbai admin: Mumbai teaching/attendance/exams/fees/library works; Pune returns 403; unrelated organization's campus returns 403; mismatched organization/campus URL returns 404.
- Verify forged school_id/campus_id ignored, guessed foreign teacher/student/enrollment/exam IDs never expose other data.
- Confirm forms show validation errors, contain CSRF tokens, preserve historical rows and audit new writes.
- Confirm responsive UI on phone/tablet/desktop; keyboard navigation, focus visibility and legible contrast.
- Verify revoked campus admins lose access, global admin without membership gets 403.
- Validate duplicate submissions, attendance corrections, absent/exempt marks, and SQL concurrency under MySQL.
- Check MySQL and SQLite CI checks separately, including production-like MySQL migrations and rollback **from a tested backup**.

## Do not claim production readiness until
- All PR reviews complete and every required GitHub CI job passes at the final commit.
- MySQL data backup/restore tested in staging, all migration checks pass.
- Authorization and workflow smoke tests pass using distinct real test accounts.
- No error logs, missing Vite manifest, or inaccessible pages.
- Deployment release approved, merged in dependency order, with rollback documented.

## Explicit limitations
Not a full production campus ERP yet. Missing: campus-owned book inventory, transactional issue/return, reconciled fee payment/refund/overpayment handling, bulk operations, teacher account provisioning, academic year transfer policies, robust search/filter/pagination for large input selectors, and production tenancy constraints for all legacy tables. Do **not** grant campus admin school-wide legacy routes as a workaround.
