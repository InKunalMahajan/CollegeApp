# Phase 27 — Separate Department Dashboards and Role Access

Department roles are separate from global User.roles and from the older unscoped `departments` / `courses` legacy tables.

## Structure

- `schools` owns many `school_departments` (code unique per school).
- `school_department_memberships` assigns a user a role within one school-owned department.
- `school_memberships` remains the school-level gate. The new `department_member` role is provisioned by an authorized school admin when they add a department assignment, and removed when the last department assignment is revoked.
- No student, teacher, class, finance or attendance table has been re-scoped or re-assigned by this phase. The department dashboard displays **only department metadata and role assignments**, not invented department KPIs.

## Access matrix

| Role | Department list | Assigned dashboard | Edit department membership | School-wide dashboard |
| --- | --- | --- | --- | --- |
| School admin | All own school | All own school | Yes | Yes |
| Department head | Assigned | Assigned | No (future approval workflow) | No |
| Department staff | Assigned | Assigned | No | No |
| Department viewer | Assigned | Assigned | No | No |
| School auditor | No | No | No | Existing read-only school analytics |
| Unassigned user | No | No | No | No |

Assignments require the user to already have a SchoolApp account. School administrators can provision a `department_member` school membership; they never change an existing school admin role. Existing auditors or other protected school roles cannot be silently converted by this workflow. A department member cannot navigate to another school's department, even by knowing an ID or URL.

## UI

- `/my-schools` shows department memberships as a school entry and links to Departments rather than an unauthorized school-wide dashboard.
- `/schools/{school}/departments` shows the user's accessible departments and lets school admins create new departments.
- `/schools/{school}/departments/{department}` shows the department role dashboard; only admins see assigned user names and role modification forms.
- The Phase 25 sidebar gains a Departments section within a school context, filtered server-side.
- Role-specific operational actions in academic, finance, HR or admissions modules are **not** automatically granted by this dashboard role. Those require future explicit authorization policies.

## Local verification

```powershell
git fetch origin
git switch --track origin/feat/phase27-department-dashboards-rbac
php artisan optimize:clear
php artisan migrate
php artisan test --filter=Phase27DepartmentDashboardsTest
php artisan test
npm run build
php artisan route:list --name=school-departments
```

**Migration writes schema:** back up any valuable development database first. Do not use `migrate:fresh` on existing data.

## Roadmap

A later phase can define department-specific resources (e.g., skills lessons, finance ledgers, HR assignments) and their own authorization policies and metrics, using new verified `school_department_id` relationships. The pre-existing global legacy department table should remain unchanged until its records have been explicitly reviewed and migrated.
