# Phase 50 — SaaS release security and operational review

## Stacked code phases
- Phase 45: explicit organization tenant registry, additive and non-destructive.
- Phase 46: authenticated request → Website Owner review → transactionally create organization + tenant + administrator membership.
- Phase 47: Owner-managed plan catalog and trial subscription records, with no live charges.
- Phase 48: Owner-managed **draft** invoice records; no payment gateway or paid state.
- Phase 49: organization branding preferences and **pending** custom domain requests, not live custom domains.
- Phase 50: Owner-only read-only release preflight, navigation and security tests.

## Access invariants
- Website Owner grants do NOT imply TEACH school membership.
- Every school/campus operation must use verified SchoolAccess/CampusAccess policies.
- Organization A must not see or mutate B's records, even if an untrusted Host header suggests its domain.
- No unverified domain is active; do NOT route requests from the saas_domains table.
- Billing never marks invoices paid, collects funds, or creates a gateway credential.
- Old TEACH data is not migrated or bulk-updated by SaaS migrations.
- Suspended tenant state and plan limits are not yet enforced across existing modules.

## Release checks
```powershell
git status
git fetch origin
git switch --track origin/feat/phase50-saas-release-hardening
php artisan optimize:clear
php artisan migrate:status
php artisan migrate
php artisan test --filter=Phase45SaasTenantTest
php artisan test --filter=Phase46SaasOnboardingTest
php artisan test --filter=Phase47SaasPlansTest
php artisan test --filter=Phase48SaasInvoicesTest
php artisan test --filter=Phase49SaasBrandingTest
php artisan test --filter=Phase50SaasSecurityGateTest
php artisan test
npm run build
php artisan school:release-check --production
```

**Do not run production settings checks as an automatic deployment approval.** Ensure MySQL backup and staging restore, TLS and trusted proxy settings, real-world account invite and email verification, security review of upload and rate limiting, database migrations, queue reliability and operational alerting.

## Commercial SaaS blockers (not implemented)
Production-grade payment provider + verified webhook signatures, idempotency, refunds and reconciliation; compliant final invoice numbering/GST tax treatment; subscription renewals, grace periods and entitlement enforcement; DNS ownership verification/TLS/custom Host routing; background jobs and backups/monitoring; accessibility, load and penetration testing; legal privacy and terms. The features in this stack are **SaaS foundations, not a finished paid SaaS launch**.

## Manual user acceptance
Log in separately as Owner, TEACH administrator, Mumbai administrator, Pune administrator, unaffiliated staff. Check all new URLs, reject unauthorized access, test onboarding approval/replay, student data isolation, plan trial duplication, invoice draft status and domain pending state. Save evidence of migration and restore checks before merge.
