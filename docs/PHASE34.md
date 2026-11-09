# Phase 34 — TEACH organization and campus administration

## Design decision

SchoolApp already models **schools** and their **campuses**. The existing school represents an **organization** (TEACH), and the campus is its location (TEACH Mumbai, TEACH Pune, TEACH Delhi). This phase avoids a duplicate organizations table and **does not migrate or seed any actual TEACH data**.

### Roles

- **Organization administrator**: the existing `school_memberships.role = school_admin` for TEACH. Can manage all campuses owned by the TEACH school, using existing school-level administration routes and the new organization campus management page.
- **Campus administrator**: a distinct `campus_memberships.role = campus_admin` assignment to exactly one campus within an organization. A campus admin receives **no** `school_memberships` school_admin role and cannot visit school-wide employee, student, finance, academic, or settings pages.
- Global `users.role = admin` is **not** an override for school or campus authorization.
- To minimize accidental multi-campus privileges, the admin grant workflow refuses assigning a user to a second campus of the same school without revoking the existing campus role first.
- Existing `auditor` and `department_member` school roles continue to work as before; they do **not** grant campus administration.

### Example permissions

| Action | TEACH org admin | TEACH Mumbai admin | TEACH Pune admin |
|---|---|---|---|
| Manage campus administrators | Yes | No | No |
| TEACH Mumbai employee/student workspace | Yes | Yes | No |
| TEACH Pune employee/student workspace | Yes | No | Yes |
| Other unrelated school | No | No | No |
| TEACH school-wide settings | Yes | No | No |

A foreign campus that does not belong to the route's school returns 404. A legitimate sibling campus without assigned membership returns 403. Any record accessed under the wrong campus returns 404.

## Implementation

- Migration `2026_11_09_150000_create_campus_access_and_assignments.php` adds:
  - `campus_memberships(campus_id, user_id, role)` with unique membership and FKs.
  - Nullable `campus_id` to `school_employees` and `students`.
  - Scoped indexes for employee/student campus queries.
  - On MySQL, unique `(school_id, id)` for campuses and composite foreign keys `(school_id, campus_id)` on employees/students.
  - On SQLite, additive columns and ownership triggers for employees/students/campuses, retaining all existing Phase 29 employee triggers without table rebuilding.
- Service `App\Services\CampusAccess` and `campus.can` middleware enforce known abilities and deny unknown permissions.
- Organization admin manages campuses at `/schools/{school}/campuses`, with existing school membership authorization. Organization admins can create, view, update names/codes/cities, and delete **only unreferenced** campuses. A campus that has members, employees (including archived), students, class sections, or teacher profiles cannot be deleted.
- Campus admin uses `/schools/{school}/campuses/{campus}/workspace` and sees only records with both `school_id = school.id` and `campus_id = campus.id`.
- Campus employee CRUD uses existing unique number/email validation, restricts fields to campus-safe profile values, and uses reversible archive/restore with reporting-tree safety checks.
- Campus student records support admission, viewing, updating and **deactivation instead of destructive deletion**; grades, attendance and audit history remain intact.
- `My Schools` shows a separate **My campuses** section for campus-only accounts.
- Organization admins must explicitly assign verified, initially unassigned employees and students into campuses; no automatic inference by city, name, CSV, job title or class is performed.
- Student assignment is refused when existing class enrollments indicate a different campus.
- Campus administrator creation grants access to existing user accounts only. It does not create passwords or promote global roles.

### Important scope limits

This implementation introduces a **campus-scoped employee/student workspace** but does not automatically retrofit existing school-wide academics, exam, fee, finance, attendance, library, or teacher assignment routes for campus administrators. Those routes remain protected by their existing school-level RBAC; a campus administrator cannot use them. Before enabling those modules for campus administrators, add verified campus ownership for every referenced row and explicit campus-specific policies and tests.

No existing employee/student is moved automatically. The new nullable campus IDs are not converted into a mandatory field. Existing school-wide import/update code continues to operate for organization administrators.

The new employee/student `campus_id` columns are **database protected** against cross-organization references: composite `(school_id, campus_id)` foreign keys on MySQL, and equivalent insert/update and campus-owner triggers on SQLite. Direct DB writes that attach a TEACH student or employee to an unrelated school's campus must fail. SQLite intentionally avoids rebuilding the employee table because Phase 29 already created cross-table integrity triggers. A separate campus hierarchy migration is not necessary for this invariant.

## Steps for a TEACH organization administrator

1. Create the TEACH school if not already present, and securely provision its existing `school_admin` membership via the established trusted administrator process.
2. Sign in as organization admin, open **My Schools → Manage campuses**.
3. Create **TEACH Mumbai** (`MUMBAI`), **TEACH Pune** (`PUNE`), and **TEACH Delhi** (`DELHI`). Only create campuses that do not already exist.
4. Use an existing user email to assign Mumbai admin under TEACH Mumbai. The organization administrator can edit campus details later; a campus administrator cannot rename or delete the campus. The user must not already be campus_admin for another TEACH campus.
5. Verify unassigned employee/student records before clicking an explicit campus assignment action. The form displays only the first 100 unassigned records in each category; use a future bulk assignment flow for larger datasets.
6. Sign in as the Mumbai admin. **My Schools → My campuses → TEACH Mumbai** should show only Mumbai records; navigating to Pune or TEACH settings must return 403.
7. Test the same isolation for Pune and Delhi.

## Windows PowerShell installation

**Back up your existing CollegeApp database before applying the migration.** Do not use `migrate:fresh`.

```powershell
cd C:\Users\LENOVO\Documents\GitHub\CollegeApp
git status
git fetch origin
git switch --track origin/feat/phase34-organization-campus-rbac
php artisan optimize:clear
php artisan migrate:status
php artisan migrate
php artisan test --filter=Phase34OrganizationCampusRbacTest
php artisan test
npm run build
php artisan serve
```

If the branch exists locally, switch to it and run `git pull --ff-only`.

**Release gate:** GitHub SQLite and MySQL CI, the full Laravel suite, staging backup/restore, manual browser tests with distinct organization/Mumbai/Pune accounts, and code review must pass before merge into any production branch.

## Rollback precautions

On MySQL, Phase 34 down() removes newly introduced FKs and columns. On SQLite the schema is connected to existing Phase 29 triggers; attempting a Laravel table rebuild while rolling back may break those triggers. Phase 34 therefore intentionally stops SQLite rollback and requires a verified database restoration or a separately tested forward migration plan. Do not run migrate:fresh on data you need to retain.
