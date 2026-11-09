# Phase 42 — Three-level administration and scoped user management

## Authorization boundaries

| Role | Authority | User-management actions |
| --- | --- | --- |
| Website Owner | Platform account lifecycle | Create staff/teacher/student accounts, edit non-owner account name/email/password, disable/re-enable non-owner accounts, appoint or revoke organization administrators |
| TEACH Organization Administrator | One organization (e.g., TEACH Pune and Mumbai) | Create/update/revoke **auditor** and **organization_viewer** organization access; manage campus administrator appointments through Phase 34 campus administration |
| TEACH Campus Administrator | One assigned campus (e.g. TEACH Mumbai) | Create/update/revoke **campus_viewer** and **campus_member** campus access only |
| Organization viewer | Organization dashboard and portal only | No user management |
| Campus viewer | Campus dashboard and aggregate operations only | No user management |
| Campus member | Campus read-only operational data | No account/role management |

Organization and campus CRUD affect **membership access records** and do not mutate/delete the underlying global account. Only Website Owner can edit or disable a platform account. The owner cannot disable or edit owner accounts through the web UI.

## Provision first owner

Website Owner is not inferred from legacy `users.role=admin`. After applying migration, log in with an existing, trusted account:

```powershell
php artisan migrate
php artisan website:owner:grant owner@example.com
```

Run the grant command **only on a trusted server terminal**, substituting the real existing account email. Never expose shell access or this command via a web route. Granting additional owners requires an equally trusted CLI process. No default owner credentials or website-owner self-registration are provided.

## URLs

- `/website-owner/users` — owner accounts and organization-admin appointments.
- `/schools/{school}/user-access` — organization viewers/auditors.
- `/schools/{school}/campuses/{campus}/user-access` — campus viewers/members.

## Safety behavior

- Unknown abilities and guessed foreign school/campus/membership IDs deny access.
- Organization admins cannot appoint themselves Website Owner or another organization admin; Owner controls `school_admin`.
- Campus admins cannot grant `campus_admin` or user-wide permissions. Organization admin controls campus admins.
- Same-organization campus-user assignment is limited to one campus per account.
- Disabled users cannot log in; previously active sessions are invalidated on their next request.
- Disabling accounts retains academic, employment, student, audit and finance history.
- All management writes audit actor, scope and role changes.
- No cross-school user-search directory is exposed. Grant forms require a known existing email and return generic failures for unknown/ineligible users.

## Testing and rollout

Back up MySQL before applying the additive Phase 42 migration; never run `migrate:fresh` against live data.

```powershell
git fetch origin
git switch --track origin/feat/phase42-three-tier-user-management
php artisan optimize:clear
php artisan migrate:status
php artisan migrate
php artisan test --filter=Phase42ThreeTierUserManagementTest
php artisan test
npm run build
php artisan route:list --path=website-owner
php artisan route:list --path=user-access
```

Verify manually with distinct accounts: Website Owner, TEACH HQ admin, TEACH Mumbai admin, TEACH Pune admin, campus viewer/member, unrelated organization admin, global legacy `admin` without owner assignment, disabled account. Validate guessed IDs and revocation while the user is logged in. Run GitHub CI and review the migration before merging.

### Limitations / future enhancements

- Organization/campus admins manage **access memberships**, not passwords or global identity.
- No email invitation/reset flow or approval workflow; account provisioning belongs to Owner.
- Account deactivation is reversible instead of physical deletion.
- Role access still coexists with legacy `User.role`; disable legacy global routes in production, and complete a separate migration if global roles need to be removed.
- Test restore and backup in staging and verify manual authorization before production release.
