# Phase 38 — Campus attendance

Campus-only student and teacher attendance recording with date/status validation, idempotent upsert, audit events, and separate scoped history. A student record must have a verified same-school/same-campus student and class enrollment. Teacher profiles must belong to the same organization and campus.

No bulk import, external attendance edits, or retroactive deletion. Historical updates to the same date are audited. Run `php artisan test --filter=Phase38CampusAttendanceTest`, `php artisan test`, `npm run build`, MySQL/SQLite CI and manual campus isolation checks.