# Phase 43 — Website Owner user account role CRUD

## UI
The existing `/users` page now displays account name, email, account type, status, and actions. Selecting **Edit role / account** takes the owner to an inline editor below the user row. The owner can change name, email, Staff/Teacher/Student type, optional password, and disable/re-enable the account without deleting historical data. The same editable account-type field is available on `/website-owner/users`.

## Permission boundaries
- Only explicitly assigned `website_owners` can access `/users`, `/users/create`, `/users` POST, or owner account lifecycle endpoints.
- The editable account type is `staff`, `teacher`, or `student`. Global legacy `admin` is **not** available for new users or privilege escalation.
- Existing non-owner legacy global admins can be converted to non-privileged account types by the owner. Existing website-owner accounts are protected from changing role, credentials, or disabling in the UI.
- `school_admin`, `campus_admin`, `auditor`, `campus_member`, and other organization/campus roles remain separate verified memberships, not mutable `User.role` choices.
- Account disable is reversible and preserves related students, exams, fees, audit records, and membership history.
- Each account creation/update/deactivation is audited.

## Verify locally

```powershell
git fetch origin
git switch --track origin/feat/phase43-user-role-crud
php artisan optimize:clear
php artisan test --filter=Phase43UserRoleCrudTest
php artisan test
npm run build
php artisan serve
```

Review account-type updates for existing legacy admins separately from organization/campus membership changes, manually verify owner/member role isolation in the browser, and back up MySQL before deployment.
