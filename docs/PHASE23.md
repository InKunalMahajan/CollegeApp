# Phase 23 — Automated QA, GitHub Actions and Release Safety

## CI checks

The `.github/workflows/schoolapp-ci.yml` workflow runs on pull requests, pushes to `main` and `feat/**` branches, and manual dispatch.

| Job | What it verifies |
| --- | --- |
| Laravel SQLite | Laravel tests using in-memory SQLite, PHP 8.4 |
| Laravel MySQL | Complete PHPUnit suite on a fresh temporary MySQL 8.4 service, PHP 8.4 |
| Frontend | `npm ci`, `npm run build`, and Vite manifest presence using Node 22 |
| PHP quality | All PHP files compile with `php -l`; Pint verifies the new Phase 23 test file |
| Dependency audit | `composer audit --locked` and `npm audit --omit=dev --audit-level=high` (advisory/non-blocking, review findings manually) |

`composer.lock` and `package-lock.json` are committed, so CI does not silently update dependency versions.

Both Laravel suites run with `RefreshDatabase` against disposable test databases only. SQLite uses `phpunit.xml`. MySQL uses `phpunit.mysql.xml`; its dedicated `schoolapp_ci` database and credentials are disposable **CI-only** examples and never production credentials. **Do not run the MySQL suite against a real database**—the tests rebuild tables.

The checked-in MySQL configuration is for the isolated workflow container. Never reuse the sample account or fixed credentials on any external database. The workflow does not request repository secrets, write deployment keys, or deploy the site.

## Local Windows commands

```powershell
git fetch origin
git switch --track origin/feat/phase23-ci-quality-assurance
php artisan optimize:clear
php artisan test
npm ci
npm run build
composer validate --no-check-publish
vendor\bin\pint --test tests/Feature/Phase23CiSmokeTest.php
```

If you already have the branch, use `git switch feat/phase23-ci-quality-assurance` and `git pull --ff-only`. To run MySQL integration locally, create a **separate disposable database** and adapt an **untracked** test configuration with its own credentials. Do not use your existing school database or `migrate:fresh` on any live data.

## Branch protection recommendations

In GitHub repository Settings → Branches / Rulesets, require review and these CI checks before merging to `main`:

- Laravel tests (SQLite, PHP 8.4)
- Laravel integration (MySQL 8.4, PHP 8.4)
- Tailwind and Vite build (Node 22)
- PHP syntax and formatting

Wait for the first workflow run to discover the exact check names GitHub displays. Do not require the advisory audit check as a security sign-off.

## Release safety checklist

- [ ] Merge stacked PRs in dependency order after code review and passing CI; do not merge a phase PR with an unmerged base that omits its dependencies.
- [ ] Verify environment variables: `APP_ENV=production`, `APP_DEBUG=false`, strong `APP_KEY`, secure database account, encrypted connections where applicable.
- [ ] Set `LEGACY_GLOBAL_ROUTES_ENABLED=false` in production; audit global/school-scoped routes again before real learner data.
- [ ] Review sessions, HTTPS, trusted proxies, access controls, audit events, password reset and MFA requirements.
- [ ] Back up MySQL, verify restoration, and test migration rollback in staging with non-sensitive data before any production migration.
- [ ] Evaluate all dependency-audit warnings; advisory status is not a substitute for a security review.
- [ ] Manually test mobile UI, accessibility, CSV export, school membership and school data isolation.
- [ ] Add monitoring, logs, retention policies, incident response, and recovery procedures.

**Not production approved.** CI is an automatic regression gate, not a penetration test, accessibility certification, or deployment readiness approval.
