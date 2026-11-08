# SchoolApp — 16 core modules

The SchoolApp scope includes **16 modules**. This is an implementation roadmap, **not a claim that all 16 modules work**.

| # | Module | Main features | Delivery |
|---|---|---|---|
| 1 | Dashboard | Statistics, summaries, charts | Phase 3; partial (counts/recent records, charts later) |
| 2 | School Management | School profile, campus settings | Phase 4 |
| 3 | User Management | Accounts, roles, permissions | Phase 3; partial (login/account creation, granular policies later) |
| 4 | Student Management | Admissions, profiles, records | Phase 1; partial (basic CRUD only) |
| 5 | Teacher Management | Staff profiles, assignments | Phase 5 |
| 6 | Academic Management | Academic years, classes, subjects | Phase 4 |
| 7 | Attendance Management | Student and staff attendance | Phase 6 |
| 8 | Timetable Management | Class schedules, teacher schedules | Phase 6 |
| 9 | Examination Management | Exams, marks, grades, report cards | Phase 7 |
| 10 | Curriculum Management | Syllabus, lessons, teaching progress | Phase 7 |
| 11 | Assignment Management | Homework, submissions, grading | Phase 8 |
| 12 | Fee Management | Fees, payments, receipts | Phase 9 |
| 13 | Communication | Notices, announcements, messages | Phase 8 |
| 14 | Reports & Analytics | Academic and administrative reports | Phase 10 |
| 15 | Library Management | Books, borrowing, returns | Phase 9 |
| 16 | Settings | School configuration and preferences | Phase 4 |

## Multi-campus data model direction
- `schools` has many `campuses`
- Users have explicit memberships / assigned roles per campus (not yet implemented)
- `academic_years`, `terms`, `programs`, `courses`, `subjects`, `class_sections`, `enrollments`
- Operational records must enforce campus and academic-year scope at the **query and policy** layers; navigation hiding alone is not authorization
- Student and staff personally identifiable data must have access controls, logging and retention policies

## Delivery order
1. **Phase 4:** schools/campuses, academic year/class/subject schema, settings and campus scoping
2. **Phase 5:** staff/teacher profiles and assignments
3. **Phase 6:** attendance and timetable
4. **Phase 7:** exams and curriculum progress
5. **Phase 8:** assignments and communication
6. **Phase 9:** fees and library
7. **Phase 10:** reports and analytics across secured modules

## Git and testing
This pull request introduces the single-source module catalog, an admin-only planning page and feature tests. It does not create placeholder routes with misleading empty screens for operational modules.

Windows 11 PowerShell:
```powershell
git fetch origin
git switch feat/16-module-management-framework
php artisan optimize:clear
php artisan test
npm run build
```

Production: prioritize authorization and personal-data protection before accepting real records.
