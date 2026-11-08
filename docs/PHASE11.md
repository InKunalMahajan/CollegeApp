# SchoolApp Phase 11 — Security foundation (partial)

## Added
- `school_memberships`: explicit role membership (`school_admin`, `auditor`) per school, unique user/school pairs.
- `/schools/{school}/security-report`: **membership-checked**, read-only school-scoped summary of attendance, graded examinations, manual fee balances and library active loans.
- `school_audit_events`: append-only application audit event table. Fee payment creation and library loan/return actions are audited in the same database transaction. Events include school, actor, action, object ID and non-sensitive metadata. They do not store payment reference, response text or student names.
- Tests: an admin without school membership is denied the new report, a member of School A cannot open School B's report, scoped summary avoids School B totals, and sensitive actions produce audit records.

## Critical non-production limitations
- **Legacy global admin modules are NOT isolated by school.** A user with the global `admin` role can still access legacy fee and student records across schools. This release does not complete multi-tenant authorization. It adds a separately scoped portal.
- Memberships have no UI. Provision only using controlled database/admin tooling after verifying ownership; never trust self-submitted membership IDs.
- This is not an immutable or forensic audit system. DB administrators and other code paths can modify the audit table. No event verification chain, external log, retention or security incident workflow.
- Only selected fee/library actions have audits; no full application-wide coverage.
- No production permission migration or default membership assignment. The new endpoint denies all users until an explicit membership exists.
- Not yet tested in the user's Windows and MySQL environment; prior phases also require test suite validation. Back up MySQL before migrations, and do not use `migrate:fresh`.

## Windows
```powershell
git fetch origin
git switch --track origin/feat/phase11-security-foundation
php artisan migrate:status
# Resolve any earlier pending duplicate role migration and back up MySQL first
php artisan migrate
php artisan optimize:clear
php artisan test --filter=Phase11SecurityTest
php artisan test
npm run build
```

Avoid real learner and financial data until global admin authorizations and cross-school data access are refactored and independently reviewed.
