# SchoolApp Phase 18 — School-scoped student login linking

## Delivered
- GET `/schools/{school}/student-accounts`: list school-owned students and linked login accounts, without a cross-school user directory.
- POST `/schools/{school}/student-accounts`: link an **existing** `role=student` user identified by email to a verified `students.school_id` record belonging to this school.
- DELETE `/schools/{school}/student-accounts/{account}`: unlink only a link whose student currently belongs to the route school.
- All routes require authentication plus `school.member:school_admin`.
- One-to-one `student_accounts.student_id` and `student_accounts.user_id` unique DB indexes already exist from Phase 8; duplicate association is rejected before insert.
- Every link and unlink records a school-scoped audit event inside the same database transaction, without storing login email or credentials in audit metadata.
- Existing assignment submissions persist on unlink; the student's assignment access ends on the next request when no link exists. Unlink does not revoke existing login sessions or reset passwords.
- School administration page includes a Student Login Accounts link.
- Security test coverage includes guest/member restrictions, cross-school ID access, duplicate user/student links, role restriction, audit events and unlinking preserving submissions.

## Security/operations
- User identities are entered by known email; login account creation and password changes are intentionally not in scope. Do not use another school's linked login: global unique user_id blocks it.
- Global legacy `/student-links` remains available **only if** Phase 15's legacy global routes are explicitly enabled (default true in local/testing, disabled in production). Keep them disabled in actual multi-school deployments.
- Locking is accompanied by unique indexes. Concurrency conflicts may still return an integrity exception, rather than a friendly validation message; investigate before exposing to production.
- No new database migrations.
- This is not a complete multi-tenant security audit, and it is not production-ready certification.

## Windows commands

```powershell
git status
git fetch origin
git switch --track origin/feat/phase18-school-scoped-student-links
php artisan optimize:clear
php artisan route:list --name=school-student-accounts
php artisan test --filter=Phase18SchoolStudentAccountsTest
php artisan test
npm run build
```

If the branch exists locally, switch to it and run `git pull --ff-only`. Save any uncommitted changes before switching. Do not run `migrate:fresh`.
