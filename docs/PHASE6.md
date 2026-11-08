# Phase 6 — Attendance and Timetable Management

## Included
- Class enrollment records link the existing student table to an existing class section.
- Admin-only daily student attendance and teacher attendance registers, statuses present/absent/late/excused, with upsert by person/date.
- Admin-only weekly timetable creation and removal.
- A subject/class/teacher combination must already have a teacher subject assignment.
- Server-side rejection of overlapping teacher or class timetable periods.
- Teacher-only `/my-timetable` listing the signed-in teacher's own periods.
- Role and validation feature tests.

## Limitations / security
- This is a **partial** Phase 6 implementation. Existing students have no school/campus ownership yet; global admin must manually enroll students in the correct class. Do not use multiple independent schools or real student data until cross-school enrollment protections and scoped authorizations are built.
- Teacher attendance here covers teacher profiles only, not every non-teaching employee.
- No teacher self-service attendance entry, student self-access, bulk attendance, holidays, timetables by term, room-conflict prevention, academic calendar, exports, or attendance analytics.
- Timetable collisions are validated during request handling, not protected by a cross-transaction database constraint; concurrent requests require additional locking/serialization before production.
- Changing/deleting prior subject assignments does not automatically remove existing timetable periods; consistency procedures should be introduced.
- The role migration duplication in existing MySQL must be reconciled before running pending migrations; do not run `migrate:fresh` on existing data.
- Requires audit trails, tenancy, change history and production privacy/security review.

## Windows setup
```powershell
git fetch origin
git switch --track origin/feat/phase6-attendance-timetables
php artisan migrate:status
# Back up MySQL and reconcile any pending duplicate role migration first
php artisan migrate
php artisan test --filter=Phase6OperationsTest
php artisan test
npm run build
php artisan serve
```

Routes:
- Admin: `/attendance`, `/timetables`
- Teacher: `/my-timetable`

Order: Create school / academic year / class section; create teacher profile and subject assignment; create/enroll students; record attendance; publish timetable periods.
