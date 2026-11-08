# Phase 20 — School data ownership and relationship integrity

This change hardens existing school-scoped reads and writes against *inconsistent historical records* linking one school's student to a different school's class, fees or library.

## Core changes
- Added `App\Services\SchoolDataScope`: reusable query builders for valid class section IDs (campus AND academic year must belong to the school), enrollment IDs (student.school_id plus valid class section), charge IDs (fee structure's class matches the same verified enrollment), and loan IDs (verified enrolled borrower and same-school book).
- School portal hides foreign-owned students even when they are incorrectly enrolled in a local class. Fee charges are included only when fee structure, class and student school ownership all match. Teachers must have a campus from the same school.
- School security summary counts only validated enrollment records, matching exam classes/subjects, matching fee charges/payments, and school-owned library loans.
- School finance/library listings and payment/issue/return endpoints use the shared scopes; return/issue/payment repeat ownership checks inside transaction boundaries.
- School academics list and attendance/results write endpoints validate canonical student ownership, school-specific academic years, and related school records.
- Fixtures for earlier security tests are updated to represent explicitly owned students; test logic and production security checks are not relaxed.
- Added `Phase20TenantDataIntegrityTest` with deliberately corrupted cross-school relationships, to verify that the portal, academics, fees, library and summary fail closed.
- No new database migrations and no automatic data rewriting.

## Important limitations
This is application-layer enforcement, not yet complete database-level referential constraints. Existing malformed records remain stored and require a *separate reviewed reconciliation plan*. Trusted users with raw database access can still create inconsistent relationships; this change does not prove the system has full multi-tenant isolation. Attendance and reports outside the edited controllers, global legacy endpoints, CSV exports and every other route need a complete independent audit.

## Required verification on Windows

```powershell
git status
git fetch origin
git switch --track origin/feat/phase20-tenant-data-integrity
php artisan optimize:clear
php artisan test --filter=Phase20TenantDataIntegrityTest
php artisan test
npm run build
```

If the local branch already exists, switch to it and run `git pull --ff-only`. Save any local modifications first. Run these against a properly configured test database; do not use `php artisan migrate:fresh` against real school data. SQLite-in-memory feature tests do not replace a separate MySQL integration test or a professional security review.

## Deployment checklist
1. Back up MySQL and test restoration.
2. Run the tests above, plus a MySQL integration test.
3. Check historical records for cross-school relationships; do not silently change student school ownership.
4. Keep `LEGACY_GLOBAL_ROUTES_ENABLED=false` on multi-school production.
5. Review authorization and sensitive data exposure before considering live learner, payment or employee data.
