# Phase 15 — Legacy global route lockdown and student ownership

## Delivery
- `school_security.legacy_global_routes_enabled` config controls legacy unscoped student and admin route access. Default **false** outside local/testing, including production. All legacy admin routes and unscoped student CRUD require `legacy.global` middleware.
- Override for isolated development only: `LEGACY_GLOBAL_ROUTES_ENABLED=true`; never do this with actual multi-school records.
- Add nullable `students.school_id` FK. Legacy students deliberately retain NULL until a reviewed/manual ownership assignment; no risky automatic backfill.
- New route `POST /schools/{school}/students` with `school.member:school_admin`: registers an owned student and writes school-specific audit event.
- School-scoped enrollment now requires a matching non-null `students.school_id`, preventing linking records without verified school ownership.
- New security feature tests for legacy fail-closed behavior, school-owned admissions, cross-school enrollment denial.
- Scoped academic dashboard includes a simple admission form.

## Critical limitations
- This is a **deliberate behavior change** when security lockdown is active: legacy `/students`, `/fees`, `/library`, `/attendance`, `/exams`, `/reports`, `/teachers`, `/users`, `/setup`, etc. return 403 even for global admins.
- Production usability depends on explicitly provisioned `school_memberships` and existing scoped routes. Read-only school portal, finance, and academics are available under `/schools/{school}/...`.
- Old unowned student records cannot be enrolled in scoped academics until a trusted, reviewed school ownership assignment has been made. A controlled admin migration/UX is pending.
- Global `/` dashboard, announcements, teacher/student self-service routes and potential other privacy surfaces have **not been fully audited**. This is not a declaration of production-ready multi-tenancy.
- Users can still change configuration and DB administrators can change data; audit events are not tamper-evident.
- Test suite may have legacy assumptions. This branch has not been executed in the user's Windows environment; review tests and any unexpected behavior.

## Deployment safety
1. Back up MySQL and verify existing Phase 11 migrations.
2. Confirm `LEGACY_GLOBAL_ROUTES_ENABLED=false` on production; verify config cache, permissions and memberships before allowing traffic.
3. Review old unassigned students and migrate ownership by verified school criteria, never by guess.
4. Run `php artisan migrate`, `php artisan test --filter=Phase15SecurityHardeningTest`, full tests and frontend build.
5. Independently audit remaining endpoints before using real learner or payment information.
