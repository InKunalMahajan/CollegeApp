# SchoolApp Phase 8 — Assignments & Communication

## Included
- Admin-only 1:1 linking between a Student role login and an existing student admission record; enforced by unique database indexes.
- Teachers create draft text homework for their **own** class/subject allocations, publish drafts, view submissions and grade responses with numeric marks and feedback.
- Published assignments are visible only to student accounts linked to records enrolled in the corresponding class.
- Students submit or revise text before the due time; graded responses cannot be modified by the learner.
- Admins create and publish role-targeted announcements; other users see only published announcements for their role or all users.
- Feature tests exercise ownership, draft visibility, grade flow and announcement audience controls.

## Explicit limitations
- No file uploads, messaging, comments, push/email delivery, parent accounts, submission attachments, bulk grading, deletion or assignment revisions.
- School announcements are **global within the application** and only filtered by role. There is **no school/campus tenant isolation** and no audience-by-school controls.
- Existing student and enrollment models still do not enforce correct school ownership; use only sample data in a single-school development environment until tenant authorization is implemented.
- Grade submission is a text-only demonstration. No audit history, immutable grading, plagiarism checking or official marking workflow.
- All Phase 8 work needs Windows 11 test and browser review; this is a draft PR.
- Resolve pending earlier duplicate `users.role` migration and back up MySQL before applying any new migrations.

## Run on Windows

```powershell
git status
git fetch origin
git switch feat/phase8-assignments-communication\ngit pull --ff-only
php artisan migrate:status
# Back up MySQL and resolve any pending duplicate role migration first
php artisan migrate
php artisan optimize:clear
php artisan test --filter=Phase8WorkflowsTest
php artisan test
npm run build
php artisan serve
```

Routes: admin `/student-links`; teacher `/teaching-assignments`; student `/my-assignments`; authenticated users `/announcements`.

Setup: Create school/campus/class and subject; assign teacher; enroll student into class; create Student-role user and link under Student Login Linking; teacher drafts/publishes homework; student submits; teacher grades; admin publishes announcements.
