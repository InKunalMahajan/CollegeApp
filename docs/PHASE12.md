# SchoolApp Phase 12 — School-scoped authorization pilot

## Delivered
- New `school.member` middleware checks route-bound `School` plus explicit `school_memberships` role (`school_admin` or `auditor`); missing membership fails closed.
- New read-only route `/schools/{school}/portal` available only to members, with school-scoped enrolled students, teachers, charges and books.
- Phase 11 school security report now uses the membership middleware, retaining its existing controller-side check as defense in depth.
- Feature tests for missing membership, cross-school access denial, school-specific rendered data, auditor and unrelated student permissions.
- No schema changes in this branch.

## Critical limitations — NOT complete multi-tenant security
- **Existing global `admin` and `staff` routes remain unscoped**, including student lists and administration, academic setup, fees, library, attendance, examinations, teacher assignments, reports and other endpoints. These must be refactored before a school-isolated production deployment.
- Student admission records lack a canonical `school_id`; this portal shows only students associated with a school's class enrollment, not all unattached admissions.
- The portal is **read-only**. It does not authorize write actions via new school-scoped endpoints; legacy write routes still have global admin privileges.
- Membership provision is manual and must use a trusted path. There is no school-selection user experience, migration of legacy users, scoped administrative membership management or permission audit coverage for all modules.
- Role `auditor` grants read access to the pilot school portal, not permission to mutate financial records.
- Security tests have not been executed on the user's Windows environment.
- Do not use real student/financial records or deploy multi-school instances.

## Windows verification

```powershell
git status
git fetch origin
git switch --track origin/feat/phase12-school-scoped-portal
php artisan optimize:clear
php artisan route:list --name=school-portal
php artisan test --filter=Phase12SchoolPortalTest
php artisan test --filter=Phase11SecurityTest
php artisan test
npm run build
```

Sample-data access requires inserting `school_memberships` through trusted administrator tooling.
