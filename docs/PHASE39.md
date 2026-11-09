# Phase 39 — Campus examinations and results

Creates campus-scoped examinations and records/upserts results only for a verified enrollment matching that examination's class. Enforces same-organization subjects, matching academic year/campus, marks bound by exam max, status-specific marks, audit logging and independent campus permissions.

No removal of historical results, no legacy school-wide route expansion, and no automated reassignment of unverified records. Results in the UI are selected from the first 300 campus enrollments; the server still validates ownership for every submission. Run `php artisan test --filter=Phase39CampusExamsTest`, full tests, build, CI on SQLite/MySQL and browser authorization checks.