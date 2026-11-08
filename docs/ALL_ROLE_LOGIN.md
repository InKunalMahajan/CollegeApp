# CollegeApp — Login for all roles

## One login page
All accounts sign in at `/login` with their own email and password. No public signup is available.

| Role | Dashboard | Student administration | User account management |
| --- | --- | --- | --- |
| Admin | Yes | Yes | Yes |
| Staff | Yes | Yes | No |
| Teacher | Yes (restricted) | No | No |
| Student | Yes (restricted) | No | No |

Role-specific school features such as teacher schedules, grading, student attendance and student profiles are future stages and **are not implemented**.

## First administrator (Windows 11)
```powershell
php artisan migrate
php artisan college:create-admin
php artisan serve
```
Choose a unique email and a long password at the terminal prompts. This command refuses to overwrite an existing email.

The administrator can then sign in and open **User accounts > Create account**, choose Admin, Staff, Teacher or Student, and supply initial credentials. Do not put passwords in Git.

## Validation
```powershell
php artisan optimize:clear
php artisan test
npm run build
```

## Security follow-ups before production
- Password reset and a forced password change on first login
- MFA for privileged accounts, email verification and audit trail
- Explicit per-resource policies and data ownership for teacher/student workflows
- Secure private delivery of initial credentials
- Complete Windows 11 integration test and backup policy

**Do not use real learner personal information until these security and privacy controls are reviewed.**
