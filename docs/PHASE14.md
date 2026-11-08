# Phase 14 — School-scoped Academic Operations (pilot)

## Added
- Membership-guarded school admin endpoint `/schools/{school}/academics` with a simple Blade dashboard.
- New scoped actions: existing-student enrollment, teacher/subject/class assignment, student attendance, timetable period creation, exam creation and grade recording.
- Resource resolvers restrict school-related IDs: campus and academic year for class sections, teacher profile and campus, subject, enrollment, teaching assignment and exam. Cross-school ID mismatches respond 404.
- Transactional writes with Phase 11 audit events (enrollment, assignment, attendance, timetable, exam and results).
- Scope-aware tests for membership, cross-school reads/writes, duplicate attendance update, invalid result/class relationships, and audit metadata.
- No database schema changes.

## Important limitations
- This is a **parallel school-specific workflow**. Legacy global `/attendance`, `/exams`, `/timetables`, `/teacher-assignments`, `/students`, `/teachers`, `/reports` and other admin endpoints remain globally accessible and **unsafe for multi-school production**.
- Student admissions do not have canonical school ownership. This implementation rejects students already enrolled in another school, but un-enrolled students have no school identity; they can be linked by ID. A student ownership migration is needed.
- Teacher profiles, subjects, academic years and campuses are still created through legacy admin workflows.
- Attendance upsert and timetable conflict detection still need MySQL concurrent-write testing. No server-side long-term audit retention policy.
- Existing app middleware permits an explicit school membership regardless of global user role. Memberships must be created only through trusted administration tooling.
- User interface is a pilot form dashboard, not a polished production SIS.
- This code has not been executed against Windows/MySQL; verify tests before merge.

## Windows
```powershell
git status
git fetch origin
git switch --track origin/feat/phase14-scoped-academics
php artisan optimize:clear
php artisan route:list --name=school-academics
php artisan test --filter=Phase14ScopedAcademicsTest
php artisan test
npm run build
```

Use sample student and financial data only until all legacy endpoints are secured or removed.
