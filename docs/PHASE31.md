# Phase 31 — Advanced Employee Management

SchoolApp Phase 31 extends the existing **school_employees** records and remains behind the Phase 30 centralized school permission registry. It is not a separate HR identity store.

## Implemented

- Employee lifecycle: **active**, **on leave**, **inactive**, and **separated**.
- Employment types: **full time**, **part time**, **contract**, and **intern**.
- Optional joining date and work email, with unique work email **per school**.
- Optional new columns allow existing employee records and imported OrgTree records to continue without modification; existing records default to **active**.
- School-specific employee status filter, organization tree, employee directory and detailed employee profile.
- Change-history panel for school administrators; logs record field **names**, not profile values.
- Existing school department and verified reporting-manager links are preserved; the Phase 29 integrity migration protects cross-school links.
- School administrators retain exclusive create/edit/import privileges; auditors can view all school employees read-only; department members see only employees attached to explicitly assigned departments. On department profiles, a manager from an unassigned department is not revealed.
- Invalid status/type values, joining dates in the future, duplicate school-scoped work emails and cross-school profile URLs are rejected.
- No automatic login-account creation, role changes, department-manager elevation or payroll effects.

## Architecture

Schema: `database/migrations/2026_11_09_130000_add_employee_lifecycle_fields.php`

Model: `app/Models/SchoolEmployee.php`

Controllers: `SchoolEmployeeController` and `SchoolEmployeeProfileController`.

Views:
- `school-employees/index.blade.php` — organization tree, filtered employee directory and administrator editing forms
- `school-employees/show.blade.php` — single employee profile, optional admin-only change history

Routes:
- `GET /schools/{school}/employees/` — school- or department-scoped tree and directory
- `GET /schools/{school}/employees/{employee}` — scoped employee profile
- Existing `POST`, `PUT` and CSV import routes remain administrator-only.

## Role permissions

| Operation | School admin | Auditor | Department member | Outsider |
| --- | --- | --- | --- | --- |
| Employee tree/directory | Full school | Read-only school | Assigned department only | Denied |
| Individual profile | Full school | Read-only school | Assigned department only | Denied |
| New/edit employee | Yes | No | No | No |
| Import OrgTree CSV | Yes | No | No | No |
| View profile change history | Yes | No | No | No |

Work email is a professional contact field; avoid storing personal email, salary, medical, or identity documents here. Only authorized users can see profiles. This release does not link SchoolApp login accounts to employee records automatically.

## Backups and migrations

`php artisan migrate` adds four lifecycle columns, an index for school+status and a school-scoped unique work email index. It does **not** delete employees or alter their manager relationships. Before migration on a valuable database, verify a backup and confirm the Phase 29 hotfix is installed.

Windows PowerShell:

```powershell
git fetch origin
git switch --track origin/feat/phase31-advanced-employee-management
php artisan optimize:clear
php artisan migrate:status
php artisan migrate
php artisan test --filter=Phase31EmployeeManagementTest
php artisan test
npm run build
php artisan serve
```

If the branch already exists locally: `git switch feat/phase31-advanced-employee-management` followed by `git pull --ff-only`. **Do not run `migrate:fresh` on existing data.**

## Validation checklist

- Test an existing pre-Phase-31 employee displays `active` and no invented joining date.
- Test school admin creates/edits status and work email; only field names appear in history.
- Test auditor cannot add/edit/import.
- Test department member cannot see other departments' employee details or manager identity.
- Test cross-school URLs, duplicate emails within the same school, and invalid dates.
- Test all existing Phase 28–30 features, MySQL CI and SQLite CI, and responsive screens.

## Known limits

This release intentionally omits payroll, personal addresses, personal contact data, leave entitlements, employment agreements and automated account provisioning. A dedicated HR data-privacy model and more granular approval permissions are necessary before those features are added. The organization tree still loads its visible employees into memory, so larger deployments will need pagination and lazy loading.
