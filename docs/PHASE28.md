# Phase 28 — Employee Organization Tree (SchoolApp)

## OrgTree.csv reference

The user-provided CSV contains 22 employees, with exactly six columns: `Employee Number`, `Display Name`, `Job Title`, `Department`, `Location`, `Reportees Count`. It does **not** contain a manager or reports-to identifier.

No named employee from the CSV is committed to GitHub. The school administrator uploads the private source file inside their own SchoolApp installation using the admin-only import form. Imported records are scoped to the selected school.

## Implemented permissions

| Role | View employee hierarchy | Add/edit employee | CSV import |
| --- | --- | --- | --- |
| School administrator | Own school, all employees | Yes | Yes |
| School auditor | Own school, read-only | No | No |
| Department member | Only employees explicitly attached to an assigned school department | No | No |
| Unassigned or other school user | No | No | No |

The global `User.role` (such as `admin` or `staff`) never bypasses the school membership. Only `school_memberships.role=school_admin` can mutate employee records.

## Organization hierarchy

The new `school_employees` table contains school, employee number, display name, job title, source department label, location, optional SchoolApp department association and an optional manager employee ID. Employee IDs are unique **within the school**.

When no manager is selected, the employee is rendered as a top-level node. The hierarchical tree uses **confirmed manager IDs**, not the unverified source CSV reportee counts. Direct reportee totals are calculated from the stored manager links.

School administrators can manually select a manager in the Add/Edit employee forms. The server rejects cross-school manager/department IDs, self-reporting, reporting cycles, and duplicates within a school.

## CSV behavior

- Admin-only, 2 MB / 1000 rows maximum; requires the six exact headers.
- Recognizes a UTF-8 BOM. Validates employee number, text lengths and nonnegative reportee counts.
- Existing employee number in the school updates profile fields while preserving verified manager and SchoolApp department assignment.
- New employees are created **without** manager links. The CSV never creates user accounts or password credentials.
- **No spreadsheet content is committed to GitHub, uploaded to the public repository, or automatically sent elsewhere.**

## Windows setup

```powershell
git fetch origin
git switch --track origin/feat/phase28-employee-org-tree
php artisan optimize:clear
php artisan migrate
php artisan test --filter=Phase28EmployeeOrgTreeTest
php artisan test
npm run build
php artisan serve
```

Back up any valuable local database before running migrations. Never run `migrate:fresh` on an existing school database. Use My Schools → Employee tree and select a school where you have school administrator membership.

## Scope limitations

- Employee records do not automatically provision login accounts.
- Payroll, leave, attendance, hiring workflow and reporting-manager approval are **not** included.
- Uploaded OrgTree reportee counts are validated but not stored as a definitive hierarchy; review and manually link managers.
- School-specific department membership filtering only includes records explicitly linked via `school_department_id`. Source department text is not automatically mapped.
- This is development code. Test CI, migrations, privacy controls and browser accessibility before production use.
