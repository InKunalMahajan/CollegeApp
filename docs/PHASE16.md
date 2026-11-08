# SchoolApp Phase 16 — School administration & permissions (pilot)

## Implemented
- `GET /my-schools`: logged-in user sees only schools for which they hold an explicit `school_admin` or `auditor` membership. All links include a school ID, and authorization remains server-side. No session-wide school switch and no implied access changes.
- `GET /schools/{school}/administration`: restricted by `school.member:school_admin`, showing members and school ownership verification tools.
- Assign and revoke **auditor-only** memberships for existing user accounts. Server-side permission rules prohibit self-granting or promoting to `school_admin`. No ability to edit or delete school administrator membership. Account creation and initial school admin grant remain trusted out-of-band operations.
- Legacy student ownership verification by admission number: assignment allowed only if `students.school_id IS NULL` and the student has at least one existing enrollment and *all* existing enrollments belong to the current school. Lock row in transaction, then record audit event.
- Membership changes and student ownership assignments are transactional with Phase 11 audit events. No private student details or email contents in audit metadata.
- Security feature tests covering denied unauthenticated and out-of-school access; school-specific switcher and admin screens; auditor-only role restrictions; cross-school revocation denial; ownership claim verification and auditable changes.
- No new migrations; depends on Phase 15 `students.school_id` and Phase 11 membership/audit tables.

## Security notes
- The school administrator screen deliberately cannot create new school administrators. Bootstrap privileges must be set using a separate, reviewed trusted path.
- School switching is just navigation to school-scoped URLs, not a mutable authorization context; it cannot grant membership.
- Existing student ownership derived from enrollment data is only as trustworthy as those historical enrollment records. Manually verify original admissions before claiming.
- User account enumeration is limited to existing known email addresses; permission to use this operation should be treated as sensitive.
- Strict legacy admin route protection from Phase 15 remains in force in production. Other teacher/student workflows and ancillary data flows are still subject to security review.
- Do not deploy as a production multi-tenant system or use real student/financial data until independent authorization and privacy audit.
- Code is committed but **not executed** against Windows/MySQL in this environment.

## Windows verification

```powershell
git status
git fetch origin
git switch --track origin/feat/phase16-school-administration
php artisan optimize:clear
php artisan route:list --name=my-schools
php artisan route:list --name=school-admin
php artisan test --filter=Phase16SchoolAdministrationTest
php artisan test
npm run build
```

If the branch exists locally, `git switch feat/phase16-school-administration` and `git pull --ff-only`. Backup MySQL before applying any outstanding migrations, and do not use `migrate:fresh`.
