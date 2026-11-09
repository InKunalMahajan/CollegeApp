# Phase 32 — Employee Dashboard and UI/UX Quality

## Goal

Upgrade SchoolApp's existing employee tree and directory into a useful, responsive employee overview without adding another employee data store or changing Phase 30 RBAC permissions.

## Delivered features

1. A four-card summary of **total visible employees**, **active employees**, **employees with SchoolApp department assignments**, and **employees with manager links**; on-leave count appears beneath the active count.
2. Search by employee **name**, **employee number**, or **job title**; combine with employment **status**, **department** and **location** filters.
3. A department overview with accessible text counts and proportional bars. The counts are based on the original visible employee set, not just the current search results, and never include unauthorized schools/departments.
4. A searchable directory with 20 records per page, accessible table headers, profile links and preserved filter query parameters across pagination.
5. A bounded reporting-tree display: if more than 250 employees match, the recursive tree is hidden with an explanation and the paginated directory remains usable. It never truncates the hierarchy silently.
6. Responsive KPI cards, filter controls, scrollable employee table and visible keyboard focus for key interactions.
7. Admin-only existing employee creation, edit and OrgTree import screens remain; reporting relationships are still only established explicitly by an authorized school admin.

## Security invariants

Every employee query begins with `school_id` ownership, then an additional `school_department_id IN (assigned IDs)` restriction when the signed-in school member is `department_member`. This same restricted base query is used for:
- all four KPI cards and status counts;
- department overview totals;
- location dropdown options;
- filtered search results, paginated directory and reporting tree.

A school auditor can read their own school's records; only a school admin can add, edit, import or change reporting lines. A department viewer does **not** learn another department's name, location, staff names, or counts via the dashboard. The department-navigation link is also shown only when allowed, and server route middleware remains authoritative.

## Scope and known limitations

The employee directory and query scopes are server-rendered Laravel Blade; no new JS package or SPA framework was introduced.

For speed and accessibility, the recursive tree only renders when there are up to 250 matching employees. The current admin edit forms still load all school employees and manager options (even if the directory is filtered); a future large-school HR workflow should use separate editing pages with server-side autocomplete or remote search.

Filtering the reporting tree hides manager nodes that do not match the filters, displaying their selected reportees as temporary top-level nodes. This is a *filtered view*, not a change to the verified reporting relationships.

The database contains no built-in salary, payroll, identity-document or private contact details in this phase. No new database migrations are needed.

## Windows 11 setup

```powershell
cd C:\Users\LENOVO\Documents\GitHub\CollegeApp
git status
git fetch origin
git switch --track origin/feat/phase32-employee-dashboard-ui
php artisan optimize:clear
php artisan test --filter=Phase32EmployeeDashboardTest
php artisan test
npm run build
php artisan serve
```

If the branch already exists locally, use `git switch feat/phase32-employee-dashboard-ui` and `git pull --ff-only`. Phase 32 has no schema changes; Phase 31 and its Windows-safe joining-date hotfix should already be installed.

## Test coverage

Feature tests cover:
- school-aware summary visibility and denial of cross-school reads;
- combined search/status/department/location filters;
- department member scoped counts, staff names, search and location options;
- rejection of invalid status or cross-school department filters;
- second-page directory results with preserved filter context;
- 251-employee fallback to paginated directory, with a clear tree-limit message.

The full Laravel SQLite/MySQL CI, PHP syntax and frontend build should pass before PR merge. UI browser testing at phone, tablet and desktop widths, plus keyboard/screen-reader testing, remains required before production release.
