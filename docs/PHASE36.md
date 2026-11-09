# Phase 36 — Campus student enrollment management (additive)

Phase 36 adds verified **campus-only student enrollment creation and read access**, building on Phase 35. It deliberately does not offer deletion, transfers, bulk enrollments, class editing, or school-wide academic permissions.

## Feature

- `GET /schools/{school}/campuses/{campus}/enrollments` with ability `campus.enrollments.view`
- `POST /schools/{school}/campuses/{campus}/enrollments` with ability `campus.enrollments.manage`
- Campus-scoped active student selector, campus class selector and paginated roster.
- Student ownership must match the requested school and campus; student must be active.
- Class ownership must match the campus and its academic year must belong to the requested school.
- Server ignores submitted school/campus IDs, with fail-closed record lookups.
- Unique student/section enrollments are idempotent; audit trail created only for new rows.
- No migration or re-assignment of legacy data.

## Boundaries

Campus admin does not gain access to school-wide academics, attendance, examinations, fees, library, finance, timetable, or teacher CRUD. Existing attendance, grade and payment history is never deleted or modified by this feature. Institutional policy for cross-year or multi-section enrollment restrictions is **not** implemented and needs business approval before such constraints are enforced. Database-level same-campus foreign-key enforcement for every linked legacy table is separate hardening work.

## Verification

```powershell
php artisan optimize:clear
php artisan test --filter=Phase36CampusEnrollmentsTest
php artisan test
npm run build
```

Verify the create form as Mumbai admin and confirm Pune/other-school student and class IDs return 404. Check SQLite/MySQL CI and a manual browser smoke test before merging. Backup MySQL before release; Phase 36 does not itself add migrations.
