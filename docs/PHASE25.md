# Phase 25 — Advanced Tailwind CSS v4 App Shell

SchoolApp shared Laravel Blade layout was redesigned without a starter kit, an external component library, or new JavaScript dependencies.

## Changes

- Desktop sidebar with compact icon-rail toggle, desktop-first content workspace and sticky header.
- Mobile off-canvas sidebar, backdrop, keyboard Escape close, focusable menu control and navigation labels.
- Expandable group menus using native HTML `details/summary` (keyboard accessible without JavaScript).
- Navigation search filters visible permitted menu entries only; `/` focuses menu search.
- Profile dropdown with name, email, role, My Schools, and POST+CSRF sign out.
- Active route highlighting with `aria-current="page"`, breadcrumb, skip link, layout width up to 1480px and footer.
- School-context links only shown for explicitly verified `school_admin` or `auditor` membership. Auditor gets read-only links; school admin also gets edit workflows.
- Legacy administration links remain gated by `school_security.legacy_global_routes_enabled`. This is UI gating, **not** an authorization control: route middleware remains authoritative.
- Accessible motion preferences and responsive layouts. No migrations, new endpoints or third-party JS frameworks.

## Local verification

```powershell
git fetch origin
git switch --track origin/feat/phase25-advanced-tailwind-shell
php artisan optimize:clear
php artisan test --filter=Phase25AdvancedShellTest
php artisan test
npm ci
npm run build
php artisan serve
```

If branch already exists, switch and `git pull --ff-only`. Inspect at 375px, 768px, 1024px and desktop. Test both expanded/collapsed sidebar and multiple roles. Screen reader and keyboard audit remains required before deployment.

### Known boundaries

- Sidebar open/collapse state is controlled on the client. `localStorage` persists only the desktop collapsed preference and does not store permissions or user records.
- Search filters **navigation labels**; it does not search school/student data.
- Only the shared shell and navigation are redesigned in this phase; existing page-specific forms and tables continue using their original Tailwind classes.
- Browser QA and CI execution are required, do not mark the phase verified from code changes alone.
