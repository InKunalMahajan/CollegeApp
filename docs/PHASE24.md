# Phase 24 — Production Readiness (preparation only)

This phase improves release safety; it does not deploy SchoolApp or certify it for real student data.

## Implemented

1. GitHub Actions now runs `npm ci` and `npm run build` **before** SQLite and MySQL feature tests. Phase 23 CI logs show 42 failures caused by absent `public/build/manifest.json` rather than database errors. Tests now run against actual built frontend assets.
2. `php artisan school:release-check` is a read-only preflight. It checks the app key, Vite manifest and writable storage. In a production environment, or with `--production`, it also checks production environment, debug disabled, HTTPS URL, legacy global routes disabled, secure and HTTP-only cookies, and non-root MySQL user.
3. CI exercises the basic release preflight before its Laravel tests.
4. Unit/feature tests ensure production checks reject unsafe settings and never print secret values.
5. Documented release, staging, backup, recovery and rollback procedures in `docs/RELEASE_RUNBOOK.md`.

## CI findings

As of the initial inspection, Phase 23 PR #31 had been merged into the Phase 22 branch. Its GitHub Actions run 42522430378 **failed both PHP jobs** because the Vite manifest was not built. Frontend and PHP lint passed. Phase 24 fixes the workflow, but every job must be rerun and pass before any release readiness claim.

## Verify locally on Windows

```powershell
git status
git fetch origin
git switch --track origin/feat/phase24-release-readiness
php artisan optimize:clear
php artisan test --filter=Phase24ReleaseCheckTest
php artisan test
npm ci
npm run build
php artisan school:release-check
```

For production-like staging **only**, configure a real staging `.env` safely, build assets, and run `php artisan school:release-check --production`. Do not change `APP_ENV` on your normal development installation just to force a green preflight; that would defeat the purpose.

## Not included

No cloud infrastructure, hosting purchase, TLS certificate provisioning, data backup execution, real database migration, data retention automation, queue worker deployment, MFA/password reset, live end-to-end accessibility certification or penetration testing was performed. Follow the release runbook and require manual approvals before production.
