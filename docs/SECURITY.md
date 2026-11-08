# Stage 2 Security Baseline

## Implemented
- Public registration is disabled; only a local console administrator bootstrap command is provided.
- Session-based sign-in, sign-out, session regeneration and invalidation.
- Throttled login route (5 attempts per minute per default limiter key).
- `auth` middleware on dashboard and student routes.
- `role:admin,staff` middleware for student management.
- Dashboard hides individual student records for teacher and student roles.
- Password cast uses Laravel's hashed cast.

## Remaining before production
- Create role administration and account lifecycle workflows
- Password reset, verified emails, MFA and recovery procedures
- Separate student/teacher data ownership and policies
- Integration tests against a fully installed Laravel 13 app
- Rate limiting and session security review behind production proxies
- Application logging, audit trails, backups and data retention policies
- Comprehensive authorization tests for all new routes

**Never deploy real student records until all remaining security work is reviewed.**
