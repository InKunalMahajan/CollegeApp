# Phase 30 — Centralized school and department RBAC

## Objective

Create a single authoritative permission registry for SchoolApp, replacing scattered permission decisions on school and department routes. Existing global User.role remains for legacy teacher/student workflows; a global admin label **does not** grant access to another school's data.

The authoritative permission registry is `config/school_permissions.php` and the resolver is `App\Services\SchoolAccess`. Permissions are **explicit allow-lists** and any unknown permission or unknown role fails closed.

## Role layers

1. Global app role: `users.role` (admin, staff, teacher, student), still controlling existing global or self-service routes. It is not a school membership.
2. School membership: `school_memberships` with role `school_admin`, `auditor`, or `department_member`.
3. Department membership: `school_department_memberships` with role `department_head`, `department_staff`, or `department_viewer`. This applies only to an explicitly assigned department in the same school.

## School-level authorization

| Permission | School admin | Auditor | Department member |
| --- | --- | --- | --- |
| School dashboard / portal | Yes | Yes | No |
| School security report / permission matrix | Yes | Yes | No |
| List permitted departments | Yes | No | Yes, with an active assignment |
| Manage departments and membership | Yes | No | No |
| Employee directory | Yes | Yes | Assigned departments only |
| Create/edit/import employees | Yes | No | No |
| View/manage students | Yes | No | No |
| View/manage academic records | Yes | No | No |
| View/manage finance | Yes | No | No |
| View/manage library | Yes | No | No |
| Manage school settings and student login links | Yes | No | No |

Department members with no active department membership cannot even list departments or employee records. School administrators are allowed through the explicit known permission registry only; a future or mistyped permission is never implicitly granted.

## Department-level authorization

| Permission | Head | Staff | Viewer |
| --- | --- | --- | --- |
| View assigned department dashboard | Yes | Yes | Yes |
| View explicitly assigned department employees | Yes | Yes | Yes |
| Modify another employee, manage financial records or grant permissions | No | No | No |

These roles are intentionally read-only; individual operational actions need a future explicit department data model and subject-specific policies. A user may have memberships in multiple departments, but cannot access another school or an unassigned department by guessing its URL.

## Integration

- Middleware `school.can:<ability>` and `department.can:<ability>` registered in `bootstrap/app.php`.
- All 37 school/department/employee endpoints receive an ability check, alongside any existing school membership middleware.
- `SchoolEmployeeController` and `SchoolDepartmentController` use the same `SchoolAccess` service for sensitive operations.
- School sidebar visibility is permission-based; it is not a substitute for backend checks.
- School role access matrix is read-only at `/schools/{school}/permissions`, available only to school administrator and auditor members via `school.security.view`.
- No new RBAC tables or migrations. Existing school and department membership tables remain the authoritative role assignment records.
- Existing teacher/student global routes and global legacy routes are unchanged. These require separate review for future expansion.

## Phase 29 MySQL CI prerequisite

The Phase 29 migration uses MySQL triggers for self-manager prevention, which may require `log_bin_trust_function_creators` to be enabled when MySQL binary logging is active and the migration DB account lacks elevated privileges. **This change only sets it on the disposable CI test MySQL server using its CI root account.** No production or user MySQL instance is modified. Before applying Phase 29 on another restricted MySQL server, consult that server's administrator; do not silently elevate the application database account.

## Local Windows verification

```powershell
git fetch origin
git switch --track origin/feat/phase30-centralized-rbac
php artisan optimize:clear
php artisan route:list --name=school-
php artisan test --filter=Phase30CentralizedRbacTest
php artisan test
npm run build
```

If the branch exists locally, use `git switch feat/phase30-centralized-rbac` then `git pull --ff-only`.

Phase 30 makes **no schema changes**. It inherits the unmerged Phase 29 hotfix branch as its base; ensure Phase 29 is fully integrated and verified before production. The change includes HTTP tests for role matrix access, unauthorized writes, cross-school resource isolation, invalid roles, and orphan department memberships.

## Next-stage limitations

This is a centralized **static role-permission matrix**, not a UI for editing arbitrary permission records. The department head cannot yet approve exams, assign teachers or update attendance. Such actions need department-scoped resources and separate policies, approval workflow and audit trail. SSO, MFA, password recovery, global teacher/student ownership audits, and penetration testing remain future security work.
