# Phase 44 — Separate Website Owner, Organization and Campus dashboards

## User experience
SchoolApp keeps one authentication system and one domain but uses independent role-scoped **workspaces**. These are not separate deployments or domains.

| Portal | Route | Access |
|---|---|---|
| Website Owner | `/website-owner/dashboard` | Explicit `website_owners` membership only |
| TEACH Organization | `/schools/{school}/dashboard` | Verified school membership with dashboard permission |
| TEACH Campus | `/schools/{school}/campuses/{campus}/workspace` | Verified matching school/campus permission |
| General workspace | `/` | Routes the account to an authorized portal, or shows legacy general dashboard when not assigned |

## Platform dashboard
- Global metadata counts only: organization and campus totals, platform accounts, active/disabled accounts, Website Owners, organization administrator assignments, campus administrator assignments.
- Organization directory lists organization name/code and campus count; no raw student academic, finance or personal data.
- An Owner does **not** automatically gain TEACH organization administration. An organization dashboard link is offered only when the account has an explicit `school_admin` membership.
- User and organization administrator management link to existing Phase 42-43 audited workflows.

## TEACH organization dashboard
- Existing per-organization analytics remain scoped by `SchoolDashboardAnalytics` and `SchoolDataScope`: students, teachers, attendance, exams, fees and library.
- Shows TEACH organization identity, organization user administration and campus administration shortcuts to authorized admins.
- Navigation is organization-oriented rather than showing global platform administration.

## Campus workspace
- Retains the existing campus-only workspace and permissions.
- Context navigation shows campus dashboard, operations and user management where allowed.
- A Mumbai campus admin cannot see Pune, the organization dashboard or Website Owner dashboard.

## Navigation and switching
- Owner pages show Website Owner-specific sidebar and branding; organization pages show TEACH branding and organization-specific sidebar; campus pages show campus branding.
- `/` prioritizes explicit owner membership, then organization administrator membership, then campus administrator membership. For users holding multiple memberships, **My Schools** lists authorized organization and campus workspaces.
- Global `admin` is not a Website Owner shortcut. Permission middleware remains authoritative even for a typed URL.
- Existing data schema and credential logic are unchanged.

## Verification
```powershell
git fetch origin
git switch --track origin/feat/phase44-separate-owner-organization-dashboards
php artisan optimize:clear
php artisan route:list --path=website-owner
php artisan test --filter=Phase44SeparateDashboardsTest
php artisan test
npm run build
php artisan serve
```

Visit `/website-owner/dashboard` while signed in as your existing Website Owner; test `/schools/1/dashboard` only if that account has an approved membership in school ID 1. Do not assume TEACH uses ID 1. Test with Website Owner, TEACH organization admin, TEACH Mumbai admin, TEACH Pune admin and legacy global admin without owner membership. Confirm 403 for unauthorized workspaces.

The change is an additive UI/routing update, with no new migrations. Back up MySQL and review GitHub CI before merging to the production branch.
