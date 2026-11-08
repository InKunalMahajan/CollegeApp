# SchoolApp Phase 22 — School Dashboard & Analytics

## Purpose

A single school-specific, read-only dashboard that reports learner, staff, attendance, examination, fee, and library metrics without exposing records from another school. Built on Laravel 13 + Blade + Tailwind CSS v4 + Phase 20 `SchoolDataScope`; no new JS charting dependency.

## Routes

- `GET /schools/{school}/dashboard` — school KPIs, attendance bars, academic metrics and finance/library summaries.
- `GET /schools/{school}/dashboard/export` — aggregate-only CSV export, with spreadsheet formula-injection escaping.

Both routes use `auth` and `school.member:school_admin,auditor` middleware, inside the existing school members' route group. No student, teacher or global administrator can access a school dashboard without explicit permitted membership. Dashboard links are surfaced from **My Schools** and the existing school portal.

## Definitions

- Students total/active: students assigned to `school_id` (lifetime). Active uses status `active`.
- Enrolled: distinct students in school-verified class enrollments; malformed cross-school enrollments are excluded.
- Teachers: teacher profiles matching school **and** campus ownership (lifetime).
- Attendance: recorded rows in the chosen date range from verified enrollments; present + late count as attended. Rate = attended / all records x 100; no records means 0%.
- Daily attendance bars: for days with attendance records in the selected period; not percentages of enrolled children and not estimates for missing days.
- Exams: exams dated in the period, graded results, student enrollment matching exam's class, school-owned subject, max marks > 0. Pass if obtained >= pass marks. Average is mean of each graded result's percentage, **not** a weighted total of raw marks.
- Fees: charges, paid amounts, and outstanding amounts are **all-time** figures from charges linked to a verified enrollment and matching fee structure's class. Collections in period are filtered on payment date. Balances are clamped to zero; overpayments require separate reconciliation.
- Library: total titles/copies belonging to school; outstanding unreturned loans with verified school-owned enrolled borrowers and school-owned copies. Other invalid historical records are excluded.

## Dates and limitations

- Default: last 30 calendar days including today, in app timezone.
- Optional `start_date` and `end_date` YYYY-MM-DD, maximum 366 inclusive days; rejected if reversed or wider than allowed.
- No client-facing personal data in the CSV. School name is treated as untrusted text and escaped for spreadsheet formula injection.
- The CSV and dashboard are derived from live database reads; there is no caching, background jobs, or new database migration.
- The application needs a production MySQL verification and a full tenant/security review. This phase reports data but does not repair malformed records.

## Windows setup and verification

```powershell
git status
git fetch origin
git switch --track origin/feat/phase22-school-dashboard-analytics
php artisan optimize:clear
php artisan route:list --name=school-dashboard
php artisan test --filter=Phase22SchoolDashboardTest
php artisan test
npm run build
php artisan serve
```

If the branch already exists locally, use `git switch feat/phase22-school-dashboard-analytics` and `git pull --ff-only`. Save uncommitted changes first; never run `migrate:fresh` on real student data. Verify views as a school admin and auditor, test an unrelated user/guest, export CSV, and try narrow date ranges.
