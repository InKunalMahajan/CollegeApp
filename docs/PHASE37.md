# Phase 37 — Campus teaching

Adds a scoped listing and idempotent creation of teacher–subject–class assignments. Only pre-provisioned teacher profiles, same-organization subjects, and same-campus class sections are accepted. No teacher account creation or destructive assignment removal. Campus administrators cannot access school-wide teaching routes.

Run `php artisan test --filter=Phase37CampusTeachingTest`, full tests, and `npm run build`. Check MySQL/SQLite CI and manually test Mumbai/Pune isolation before merge.