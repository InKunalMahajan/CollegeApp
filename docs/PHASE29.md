# Phase 29 — Employee database integrity hardening

## Purpose

Phase 28 already checks whether a selected manager or department belongs to the current school in Laravel validation. This phase makes **the database reject cross-school links**, even if a future command, import, job or raw SQL bypasses application validation.

## Scope

The following associations must resolve within the **same** school:

- `school_employees.(school_id, manager_id)` → `school_employees.(school_id, id)`
- `school_employees.(school_id, school_department_id)` → `school_departments.(school_id, id)`

Null manager/department IDs remain valid for unassigned employees. An employee cannot be their own manager.

### MySQL 8.4+

The additive migration creates an employee `(school_id, id)` unique key, two composite foreign keys and a self-manager CHECK constraint. The original single-column foreign keys are **retained**. Both referenced school/ID pairs are indexed uniquely. MySQL enforces these rules for inserts and updates, including attempts to move a referenced manager or department to another school.

### SQLite

SQLite cannot add composite foreign keys to existing tables using a simple ALTER TABLE statement. To avoid rebuilding an existing self-referential employee table, the migration creates three database triggers:

1. Reject employee inserts with an invalid or foreign-school manager/department or self-manager.
2. Reject employee updates with those invalid relationships and any change that leaves existing direct reportees in another school.
3. Reject moving a department to another school while employees reference it.

The original SQLite single-column foreign keys remain and continue preventing missing IDs. SQLite must have `PRAGMA foreign_keys = ON` (Laravel normally enables this) for its original single-column constraints. These triggers independently enforce school ownership.

**Limit:** The database prevents direct self-reporting; it does not prevent arbitrary multi-node manager cycles introduced by manual SQL. Laravel's Phase 28 controller rejects cycles for normal authorized updates. A separate graph-integrity policy would be needed for arbitrary database writers.

## Pre-existing data: refuse to migrate on violations

Before changing any constraints, the migration runs read-only checks for:
- cross-school or missing manager references;
- cross-school or missing department references;
- any employee whose manager ID equals their own ID.

If any violation exists, migration **throws an error** and will not attempt to silently repair data, reassign schools, or remove any employees.

**MySQL note:** Schema changes are not always transactional. Review the source and ensure a tested backup exists before migration. If an unexpected database error occurs partway through DDL, do not blindly rerun or roll back—inspect keys and constraints, then reconcile safely.

## Before applying to local development

1. Record the active database and take a verified backup. Do **not** run `migrate:fresh`, especially if OrgTree data has already been imported.
2. Review these read-only SQL checks in your own MySQL client (both should return zero rows):

```sql
SELECT e.id, e.school_id, e.manager_id, m.school_id AS manager_school_id
FROM school_employees e
LEFT JOIN school_employees m ON m.id = e.manager_id
WHERE e.manager_id IS NOT NULL
  AND (m.id IS NULL OR m.school_id <> e.school_id OR e.manager_id = e.id);

SELECT e.id, e.school_id, e.school_department_id, d.school_id AS department_school_id
FROM school_employees e
LEFT JOIN school_departments d ON d.id = e.school_department_id
WHERE e.school_department_id IS NOT NULL
  AND (d.id IS NULL OR d.school_id <> e.school_id);
```

3. With the correct disposable development/test database configured, run:

```powershell
git fetch origin
git switch --track origin/feat/phase29-employee-db-integrity
php artisan optimize:clear
php artisan migrate:status
php artisan migrate
php artisan test --filter=Phase29EmployeeIntegrityTest
php artisan test
npm run build
```

If the branch already exists, switch to it and run `git pull --ff-only`.

## Verification and CI

The new Phase 29 test class runs on both **SQLite** and **MySQL** GitHub Actions jobs. It uses Laravel's query builder for direct INSERT/UPDATE attempts (rather than only HTTP form validation), asserting that the database itself rejects:

- another school's manager;
- another school's department;
- changes from valid relationships to invalid ones;
- moving a referenced manager or department out of the owning school;
- assigning an employee as their own manager.

A control test confirms normal valid relationships still persist.

## Rollback

The `down()` migration drops only the **new** composite keys/CHECK (MySQL) or three triggers (SQLite). It leaves the employee records and the old single-column foreign keys intact. Rollback weakens school isolation and is intended only for controlled review environments. For live databases, prefer a reviewed forward fix and a tested restoration plan.

**Not a production deployment:** No user database was accessed or modified when creating this PR. Real school data remains blocked pending full security review, staging rollout, backup restoration testing and approval.
