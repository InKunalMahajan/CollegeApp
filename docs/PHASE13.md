# SchoolApp Phase 13 — School-scoped Finance & Library Pilot

## Implemented
- Explicit `school.member:school_admin` authorization on new school-specific finance/library routes.
- Read-only school fee-charge list and library inventory/loan list; school-specific forms for manual fee payments and book issue/return.
- Cross-school charge, book-copy and borrower-enrollment checks on every new write endpoint; unknown or outside-school objects return 404.
- Transactional payment balance validation, loan availability locking and audited successful mutations.
- Feature tests cover unauthenticated/non-member denial, auditor write denial, cross-school access, scope filtering, audit logs.

## Essential security limitation
- **Existing global /fees, /library, /reports, /students, /teachers, /attendance, /exams and other administration routes still permit global admin access. These remain unsafe for multi-school production.** Phase 13 introduces a safer parallel workflow; it does not automatically migrate or disable the original routes. Users must not use real learner or payment data yet.
- Scope authorization trusts explicitly provisioned school memberships. It does not provide a membership management UI or restrict global account privileges.
- Fee structures and copies must still be created through legacy admin processes; a fully scoped creation workflow is not available.
- No MySQL concurrency tests yet, no payment gateway settlement, refunds, accounting audit or multi-school completeness guarantee.

## Windows verification
```powershell
git fetch origin
git switch --track origin/feat/phase13-scoped-finance-library
php artisan optimize:clear
php artisan route:list --name=school-finance
php artisan test --filter=Phase13ScopedFinanceLibraryTest
php artisan test
npm run build
```

This branch has no new migrations; it depends on the Phase 11 school membership and audit migration.
