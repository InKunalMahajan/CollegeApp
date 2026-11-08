# SchoolApp Phase 17 — Teacher & student self-service isolation

## Scoped changes
- Teacher assignment listing, creation, publication, submission viewing and grading now require the assignment's teacher, section/campus, academic year and subject to match the signed-in teacher's school. Forged cross-school teaching assignments do not confer access.
- Grading checks that the submission enrollment belongs to the assignment's class and school. Submissions screens suppress submissions from inconsistent foreign student records.
- Student assignment listing and submission require a linked student record with non-null `school_id`, a matching school enrollment, and school-consistent teacher/subject. A student's own record is the authorization source, not posted school IDs.
- Teacher self-service curriculum progress and timetable pages add school-consistency checks. The My Teaching page filters school-inconsistent nested relationships.
- Added isolation tests for School A vs B students and teachers, forged subject assignments and unassigned legacy students.
- No migrations, no new UI routes, and no changes to membership provisioning.

## Migration and compatibility impact
- Legacy students without `students.school_id` cannot submit coursework until school ownership is verified using Phase 16's controlled workflow; previously available assignments may disappear by design.
- Historical malformed teacher subject assignments spanning schools are now filtered or denied; review and correct their ownership.
- Any older assignment tests creating students without `school_id` need updated fixtures, not weakened permission checks.

## Remaining risks
- Legacy global admin routes still rely on Phase 15 lockdown; do not enable these in multi-school production.
- Student link management remains on legacy admin routes; no new school-scoped link workflow yet.
- Model-level constraints, cross-school referential integrity, concurrent update tests, and deep audit of every self-service workflow are still pending.
- These changes have not been executed against the user's Windows/MySQL instance. Run tests, especially historic assignment tests, before merging.
- Do not treat this branch as production-ready multi-tenant certification.

## Windows validation

```powershell
git status
git fetch origin
git switch --track origin/feat/phase17-self-service-isolation
php artisan optimize:clear
php artisan test --filter=Phase17SelfServiceIsolationTest
php artisan test
npm run build
```

No additional migrations in Phase 17; Phase 15 student ownership is required.
