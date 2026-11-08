# SchoolApp — Release and Recovery Runbook

**Classification: development/staging guidance, not production approval.** Never run test suites or `migrate:fresh` against production or any existing school database. Do not upload `.env`, database dumps, learner data or credentials to GitHub.

## 1. Release gates

- PRs must be reviewed and merged in dependency order. All required GitHub Actions jobs (SQLite, MySQL, frontend and PHP quality) must be green on the target commit.
- Review failing advisory Composer/npm dependency audits and decide how to remediate each result.
- Verify school-scoped authorization, audits, identity lifecycle, student privacy, payments, and accessibility manually with non-sensitive staging data.
- Confirm a secure, isolated hosting environment and a named operator responsible for monitoring, backup, rollback and incident response.
- Keep a release identifier, code commit SHA, database migration list, responsible reviewer and approval notes in a separate release record.

## 2. Production environment

Use an **untracked, server-owned** `.env` that is readable only by the application account. Replace placeholders with values in your secrets manager.

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://YOUR-DOMAIN`, `APP_KEY` generated **once** and retained securely across releases.
- `LEGACY_GLOBAL_ROUTES_ENABLED=false` (required for multi-school deployment).
- MySQL: least-privileged application account, unique password, network firewall, secure transport and restricted backup identity. Never use MySQL `root` for the web app.
- Session: `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, appropriate `SESSION_SAME_SITE=lax` or an explicitly reviewed setting.
- Use HTTPS and correctly configured trusted proxies, a private filesystem for logs, safe mail provider and real queue worker if background processing is needed.
- Ensure web server root is `public/` and never exposes `.env`, `storage/`, code, or SQL backups.
- Schedule centralized monitoring for app errors, failed authentication, queue failures, disk and DB capacity, and TLS expiry. Avoid storing sensitive data in logs.

The Phase 24 preflight is a configuration check, not a penetration test.

## 3. Non-destructive staging validation

Use a **separate** staging database with safe synthetic data. Install dependencies with your lockfiles, then:

```bash
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan school:release-check --production
php artisan migrate:status
php artisan route:list --path=schools
```

Do not attempt to make `--production` pass by weakening HTTPS or session requirements. For database migration testing, restore a de-identified staging snapshot, rehearse the exact migrations, validate user flows and audit logs, and measure downtime. Changes to schema require specific review.

## 4. Backup and restoration

Before **every** schema change:
1. Take a timestamped, encrypted **MySQL logical or physical backup** using your provider's supported tooling.
2. Store encrypted backups outside the web root and separately from the application host. Limit keys and access; maintain retention and a secondary protected copy.
3. Record the database version, schema migration state, backup identity, UTC timestamp, size, checksum and restore instructions. Do not paste backup passwords into command history.
4. **Restore to a disposable isolated database** and verify tables, row counts, school relationships and application sign-in using synthetic accounts. Do not infer a backup is valid from file size alone.
5. Record an explicit rollback decision point and the operator responsible for recovery.

Restoration to a live database requires an incident decision and coordination. Replaying schema down-migrations can destroy data; never use `migrate:rollback` indiscriminately. Evaluate forward-fix, restoring a verified backup, and reconciling writes made after the backup before choosing an action.

## 5. Manual deploy sequence (only after approval)

1. Verify green CI for the exact commit and approved backups/restoration exercise.
2. Put application in maintenance mode if the migration is not fully backwards compatible; notify operators.
3. Deploy versioned code and pinned Composer/npm dependencies to a new release directory; build frontend assets in CI or on a build host.
4. Check `php artisan school:release-check --production` on the intended runtime configuration.
5. Apply only reviewed migrations with `php artisan migrate --force` after confirming the target database and backup. This is a **write operation** and must not be executed during a dry-run.
6. Cache configuration/routes/views as appropriate and restart workers gracefully; verify uptime, user roles, tenant isolation, dashboard, CSV, school audit events, finance and library.
7. Remove maintenance mode, watch error and performance dashboards, and confirm release with the responsible reviewer.

No deploy automation or migration has been executed by this phase.

## 6. Rollback / incident response

- For application-only regressions, roll back to a known-good release whose code is compatible with the **current** schema.
- For migrations, consult the reviewed rollback design. Do not assume a backwards code deploy can undo schema changes.
- Isolate access and preserve evidence when suspecting a cross-school privacy breach. Rotate affected credentials after assessing impact and notify responsible staff according to policy.
- Restore only after verifying backup integrity and accounting for changes since the snapshot; document recovery time and data loss.
- Review monitoring and audit trails before reopening; publish an incident report as appropriate.

## 7. Release sign-off checklist

- [ ] Required CI checks green for deployed commit
- [ ] Security and privacy review completed
- [ ] Backup successfully restored in isolation
- [ ] Staging smoke tests and database migration rehearsal passed
- [ ] Accessibility and mobile navigation reviewed
- [ ] Production preflight passed without insecure overrides
- [ ] Web server and TLS configuration independently checked
- [ ] Monitoring/alerting, incident owner and recovery runbook confirmed
- [ ] Release approved explicitly by authorized owner

**Do not store real learner data until these gates and unresolved security work are signed off.**
