# SchoolApp — Phase 9 Fees and Library

## Included (partial MVP)
- Admin-only class fee structures in INR stored as integer paise, with due dates.
- Issue a fee charge to each enrollment with idempotent repeated issuance. Charge amounts are snapshots of the structure at issuance time.
- Record manual cash/UPI/bank/card/other payments, enforce remaining balance under a database transaction and row lock, and show internal payment acknowledgment.
- School-specific book title catalog, unique physical-copy barcodes, availability status, loan register, issue/return dates and staff attribution.
- School consistency checked before lending to a class enrollment. Loan issue/return uses a transaction with a copy row lock.
- Feature tests for authorization, duplicate charges, decimal precision, overpayment, receipt display, double checkout, returns and cross-school borrowing.

## Important limitations
- This is **not** a production payment system: no gateway integration, settlement validation, formal GST invoices, refunds, adjustments, reconciliation, auditing, exports, concession plans or permissions beyond global administrator role.
- Payments are manually recorded acknowledgments only. Never use them as proof of bank settlement.
- Student/enrollment ownership is still not tenant-safe across schools. Global admins can view all schools. **Do not use real learner financial data until multi-tenant authorization and security are implemented.**
- Library doesn't include fines, lost-copy workflows, renewals, notifications, ISBN deduplication, physical shelves or per-campus stock boundaries.
- Attendance and other tests should be rerun after Phase 8 fix; not executed by GitHub connector.
- SQLite and MySQL have different locking semantics: review concurrency in MySQL under load prior to production.

## Windows 11 verification

```powershell
git status
git fetch origin
git switch --track origin/feat/phase9-fees-library
php artisan migrate:status
# Back up your MySQL DB and resolve pending earlier migrations before proceeding.
php artisan migrate
php artisan optimize:clear
php artisan route:list --name=fees
php artisan route:list --name=library
php artisan test --filter=Phase9FeesLibraryTest
php artisan test
npm run build
php artisan serve
```

Admin pages: `/fees` and `/library`. Receipt page: `/fees/receipts/{payment}`.
