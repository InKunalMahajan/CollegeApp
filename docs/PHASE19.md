# SchoolApp Phase 19 — School-scoped student management

New endpoints are accessible only through `auth` and `school.member:school_admin` middleware:
- `GET /schools/{school}/students`: school-specific student list with name/admission search, status filter, pagination of 15
- `GET /schools/{school}/students/{student}`: profile view
- `GET /schools/{school}/students/{student}/edit`: edit form
- `PUT /schools/{school}/students/{student}`: validated profile update, audited transaction

Existing Phase 15 `POST /schools/{school}/students` continues to handle school-specific admissions. No new migrations.

## Authorization and data integrity
Every query and route-bound student is checked against the school in the URL, so guessing IDs from other schools returns 404; no global student lists or edit pages. The school ID cannot be changed using the form or update endpoint (explicit field allowlist). Admission number and email remain globally unique according to existing schema. Updates record a school-scoped audit event with changed field names and status transition; no email/name PII values in audit context. Statuses: active, inactive, graduated. Hard deletion is intentionally not part of this phase, preserving attendance, grades, loans and academic history.

Phase 19 uses role membership, not a global admin flag. School admins must already be provisioned and remain subject to Phase 15 legacy route lockdown.

## Validation on Windows
```powershell
git fetch origin
git switch --track origin/feat/phase19-school-scoped-students
php artisan optimize:clear
php artisan route:list --name=school-students
php artisan test --filter=Phase19SchoolStudentsTest
php artisan test
npm run build
```
Save uncommitted work before switching. If the branch already exists locally, use `git switch feat/phase19-school-scoped-students` and `git pull --ff-only`.

Security note: the implementation needs independent runtime tests and further end-to-end multi-school review before real learner records or production usage.
