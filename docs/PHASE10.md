# SchoolApp Phase 10 — Reporting and Analytics

## Implemented (partial)
- Admin-only `/reports` dashboard; optional class filter and inclusive start/end date inputs.
- Attendance grouped by class, showing recorded entries, present+late entries, absences and recorded-entry attendance rate.
- Examination results grouped by exam: graded count, pass count, pass rate, average percentage from graded results.
- Fee charge counts, total charged, period manual payments and lifetime outstanding balances. Amounts stored/calculated as integer paise.
- Library loans issued during period, how many of those have since been returned, and current active loans.
- Four CSV exports: attendance, exams, fees and library. CSV values starting with spreadsheet-formula prefix characters are escaped.
- Feature tests for access control, aggregate calculations, CSV and filter validation.
- No new database schema.

## Metrics definitions
- Attendance counts only records, **not expected attendance days**. Present and late count as attended; excused is included in the denominator.
- Exams include only `graded` results, exclude absent and exempt. The grouped average is a percentage of total graded marks out of max marks.
- Fees show all-time charges and outstanding balances even when the filter is a narrower period; period payment count/amount are filtered by `paid_on`. Payment amounts are unverified manual records.
- Library period loans are filtered by `issued_on`. Active loans count all unreturned loans, even from before the period.

## Important limitations
- **Not multi-tenant isolated**. Administrators can see data across the application's schools. Do not use with real school information until scoped authorization and privacy controls are built.
- No student-identifying records in CSV exports; reports are class/exam summaries.
- No chart graphics, PDFs, report schedules, fiscal adjustments, audited ledger, drill-down, dashboards for teachers, official grades or regulatory financial reports.
- CSV export should be opened only by trusted school administrators.
- Testing is pending execution on Windows. Previous Phase 9 also needs full verification.

## Windows verification
```powershell
git status
git fetch origin
git switch --track origin/feat/phase10-reports-analytics
php artisan optimize:clear
php artisan route:list --name=reports
php artisan test --filter=Phase10ReportsTest
php artisan test
npm run build
php artisan serve
```
Admin URL: `/reports`. Export: `/reports/export?type=attendance&start_date=2026-10-01&end_date=2026-10-31`.
