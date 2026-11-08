# SchoolApp — Phase 7 Examination and Curriculum Management

## Implemented (partial module)
- Admin-only examination setup by class section, subject and date, maximum/passing marks.
- Examination results for enrolled students only. Result statuses: graded, absent, exempt.
- Percentage calculation, demonstration-grade bands (A+ 90%, A 75%, B 60%, C 40%, D below 40% when passing; F below exam's pass threshold).
- Admin-only syllabus units and ordered lessons with objectives and estimated teaching minutes.
- Teacher-only teaching progress for personally assigned subjects/classes: not started, in progress, completed; completion date and remarks.
- Validate exam subject belongs to the class school; reject result entry for another class, marks above exam maximum, and teacher access to another teacher's progress.
- Feature tests on SQLite in-memory testing database.

## Deliberate limitations
- Grades are **demonstration values**, not official grading standards. No official report cards, grade publication, weighted term averages, grade scales, mark imports, moderation, audit history, or student result visibility.
- One exam per unique class/subject/name; no edit/delete workflows yet, to avoid overwriting results accidentally.
- Curriculum units are shared by all classes for a subject, and progress is per teacher subject assignment. No syllabus versioning, content publishing, or formal assessments yet.
- No separate subject offering or academic-year curriculum version. Historical progress can shift if teacher assignments are removed. Preserve audit history before using this for real education records.
- Student records are not currently school-scoped; cross-school class-enrollment policy checks and tenant isolation are still required. Admin access is global. **Do not store real learner performance data until privacy/access restrictions are strengthened.**
- All tests still need execution on Windows 11; the PR remains draft.

## Windows 11 verification

```powershell
git status
git fetch origin
git switch --track origin/feat/phase7-exams-curriculum
php artisan migrate:status
# Back up MySQL and reconcile any pending earlier role migration
php artisan migrate
php artisan optimize:clear
php artisan test --filter=Phase7AcademicTest
php artisan test
npm run build
php artisan serve
```

Admin pages: `/exams`, `/curriculum`. Teacher page: `/my-curriculum`.

## Data workflow
1. Create school, academic year, campus, class section and subject.
2. Enroll students into the class.
3. Create the examination; enter results for enrolled students.
4. Create subject curriculum unit, then lessons.
5. Assign a teacher to a subject/class; that teacher logs in and marks progress.

**Migration warning:** If your MySQL already has a `users.role` column but its Laravel migration is pending, reconcile the migration history before proceeding. Never run `migrate:fresh` on an existing database with user records.
