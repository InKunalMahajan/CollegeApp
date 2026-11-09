# Phase 35 — Read-only campus operations overview

Phase 35 extends the Phase 34 TEACH organization/campus permission model **without opening legacy school-wide write routes** to campus administrators.

## Added

- `GET /schools/{school}/campuses/{campus}/operations`
- Explicit `campus.operations.view` permission checked by `campus.can`.
- Read-only aggregate counts for teacher profiles, class sections, verified enrollments, student/staff attendance, exams/results, fee charges/payments, library loans and timetable entries.
- Campus workspace link to the new overview.
- Cross-campus, cross-organization and global-role-bypass authorization tests.

## Ownership and data-quality constraints

- Campus must belong to the requested organization.
- All student-linked aggregates require **both** the student's `school_id/campus_id` and the enrollment's class section/campus owner to match.
- Teacher attendance and timetable aggregates require teacher profile ownership.
- Aggregates exclude unassigned or mismatched records. A count is the number of records, **not** a payment total, attendance rate or academic success rate.
- Existing school-wide academics, fees, attendance, examination, library and timetable routes remain restricted to school administrators. No payment or academic editing is enabled for campus admins.
- No migrations or existing data modifications are introduced.

## QA / rollout

Start with the Phase 34 branch and a MySQL backup. Run:

```powershell
php artisan optimize:clear
php artisan test --filter=Phase35CampusOperationsTest
php artisan test
npm run build
```

Then verify separately as TEACH organization admin, TEACH Mumbai admin, TEACH Pune admin and an unrelated school admin. Confirm browser navigation and that the school-wide editing routes still return 403 to campus admins. GitHub MySQL and SQLite CI are required.

## Further phases (not delivered here)

Campus-level CRUD for academic setup, class assignment, teacher profiles, attendance, exams, fees and library requires a dedicated migration/integrity assessment, transaction-safe policies, authorization tests, and history-preserving workflows **for each** module. Do not infer ownership of legacy unassigned rows. These features are not automatically enabled by Phase 35.
