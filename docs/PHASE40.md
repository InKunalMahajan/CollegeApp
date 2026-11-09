# Phase 40 — Campus finance and library visibility

Adds read-only tenant-scoped charges, payments and student library loans. Student and class section must both match organization/campus. Fee structures must belong to the same class and campus, and book records must be owned by the organization. No modification of money or book inventory is permitted.

**Deferred:** Finance payment mutations need atomic balance checks, idempotent payment references, refund/reversal policy and audit controls. Library issuance/returns need campus-owned inventory and transactional conflict protection. Granting these operations using school-only `library_copies` would create cross-campus risk.

Run `php artisan test --filter=Phase40CampusFinanceLibraryTest`, full tests, frontend build, MySQL/SQLite CI and manual access checks. Do not treat visibility as full fees/library CRUD.