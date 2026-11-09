# Phase 51 — UI/UX, content quality, visual themes and typography

**SaaS backend roadmap is on hold.** This phase changes only appearance settings and user-facing copy. It does not affect admissions, exam marks, attendance, tenancy, finance, subscriptions, billing or custom-domain routing.

## Design direction

- Calm and simple administrative UI, consistent panels/cards, clear content hierarchy.
- Workspaces remain distinct: Website Owner default theme and each organization (TEACH Mumbai/Pune inherit their organization's theme).
- A Website Owner can set platform primary color, typography and spacing at `/website-owner/appearance`.
- A TEACH organization administrator can set its own display name, primary color, typography and spacing at `/schools/{school}/saas/branding`. A school brand overrides the platform default only inside its own school and campuses.
- All pages share the base app layout and CSS tokens; no new starter kit or external font CDN is required.

## Customizable values

| Setting | Choices |
|---|---|
| Primary color | Any valid 6-digit hex with white-text contrast of at least 4.5:1; dark indigo, purple, blue, teal or slate presets |
| Font family | System UI, Arial, Verdana, Georgia |
| Base font size | 14, 16, 18, 20 px (16 px recommended) |
| Layout spacing | Comfortable or compact |
| Organization title | Text up to 120 characters |

All values are validated. Font choices map to an allowlisted CSS font stack; untrusted CSS fragments are rejected. Server-rendered CSS uses validated values and Blade escapes them. Large base sizes use scalable rem typography. Typography is set at document level so existing Tailwind UI inherits it.

## Content and accessibility standards

- Titles describe the task, not internal database structures.
- Descriptions explain scope and who is allowed to change settings.
- Buttons should start with a verb (Save, Create, Request, Review).
- Forms have visible labels, focus states and errors. Never rely solely on color to convey success/failure.
- Font and color settings must preserve legibility in body text, buttons and controls.
- Ensure narrow-screen usability and keyboard navigation; manually check 200% browser zoom.
- Respect user motion preferences.

## Testing

```powershell
git status
git fetch origin
git switch --track origin/feat/phase51-design-system-typography
php artisan optimize:clear
php artisan migrate
php artisan test --filter=Phase51AppearanceTest
php artisan test
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000/website-owner/appearance` as an explicit Owner and try color, font, size and spacing settings. Then sign in as TEACH organization admin and open its Branding & Domains page. Verify TEACH changes do not affect other organizations or the Owner dashboard. Check phone, desktop, keyboard, zoom and dark/bright color readability.

## Important limitations

- These are **organization-level preferences**, not per-user individual settings. The platform style is the fallback for unbranded organizations.
- Only primary color is configurable in this phase; full secondary/tertiary token maps and a dark-mode toggle are not included.
- Some legacy components may retain fixed indigo/pixel styling; the shared design tokens apply to the main shell and common buttons/links, with follow-up component audit needed.
- This phase does not replace content across every old feature page; it introduces clearer settings content and design writing guidance.
- Back up the database, review CI and conduct manual accessibility/contrast checks before a production release.
