# Phase 26 — Unified Dashboard, Form, Table and Detail Styling

## What changed

Phase 25 provides the responsive shell. Phase 26 introduces a consistent **content design layer** across all authenticated SchoolApp views using native Laravel Blade and Tailwind CSS v4.

- `schoolapp-page` wrapper scopes all content styles to authenticated application pages. Login and other standalone pages are not affected.
- `schoolapp-panel` is adopted by existing panel surfaces across student, teacher, curriculum, admissions, examinations, finances, reports, library and setup modules.
- Common form fields gain consistent spacing, borders, focus/invalid/disabled states and label hierarchy without changing names, methods, values or CSRF tokens.
- All tables inherit readable uppercase table headings, consistent cell padding, gentle hover feedback and tabular numbers.
- Existing overflow-x table containers have reusable `schoolapp-table-wrap` hooks for responsive behavior.
- Consistent button, empty-state, toolbar and print styles complement the Phase 25 shell.
- The code contains no new migrations, controllers, routes or npm dependencies. All data access and school authorization rules are unchanged.

## Important design limits

This is a cross-site **styling unification** rather than a pixel-perfect custom page-by-page redesign. Each legacy form retains its existing field order and business logic. Several legacy admin pages remain gated by `LEGACY_GLOBAL_ROUTES_ENABLED`; visibility does not confer authorization. Login remains intentionally separate from the authenticated shell.

## Verify on Windows

```powershell
git status
git fetch origin
git switch --track origin/feat/phase26-unified-pages-forms-tables
php artisan optimize:clear
php artisan test --filter=Phase26UnifiedPagesTest
php artisan test
npm ci
npm run build
php artisan serve
```

If branch exists, use `git switch feat/phase26-unified-pages-forms-tables` and `git pull --ff-only`. The project must not be deployed until CI and security reviews are complete.

### Manual QA
- Verify 375px, 768px, 1024px and large desktop breakpoints.
- Test student, staff, teacher, school administrator and auditor access separately.
- Check tabbing, text zoom, active focus states, validation, native select boxes and table horizontal scrolling.
- Review dashboard KPI cards, admissions, fees, library, examinations, reports and printable payment receipts.
- Confirm the shell does not alter login, permissions, routes or posted field values.

This PR is a styling enhancement; no production operation or database migration is performed.
