# Security Review and QA Findings

## Summary

Read-only review of the RentaBike application and its configured MySQL database. No application files were changed and no migrations were run during the audit. The configured database was accessible for metadata and aggregate-only checks; borrower and account records were not inspected.

## Security findings

| # | Severity | File | Lines | Vulnerability | Confidence |
|---|----------|------|-------|---------------|------------|
| 1 | 🟠 HIGH | [RentalController.php](my-project/app/Http/Controllers/Staff/RentalController.php) | 131-137 | Any authenticated staff member can request a borrower ID document by rental ID. The handler validates the stored path and file existence but does not authorize access to that rental. | 9/10 |
| 2 | 🟡 MEDIUM | [StaffPasswordResetController.php](my-project/app/Http/Controllers/Auth/StaffPasswordResetController.php), [AdminPasswordResetController.php](my-project/app/Http/Controllers/Auth/AdminPasswordResetController.php) | 122-126; 143-149 | Password reset clears remember-token fields but does not revoke already-authenticated sessions. Existing sessions may remain usable after a password reset. | 8/10 |

## Other QA and operational findings

### Rental concurrency

The configured rental-open procedure checks bike availability before starting its transaction and has no `FOR UPDATE` lock. The rental-close procedure checks the rental's active state before its transaction and also has no row lock. The database has no unique constraint limiting active rentals per bike or overtime rows per rental. Concurrent requests could therefore race and create inconsistent rental/overtime records.

The aggregate checks found no multiple active rentals per bike or multiple overtime rows per rental at the time of review. This is a concurrency risk, not an observed data-integrity incident.

- [Rental-open procedure migration](my-project/database/migrations/2026_10_25_110000_create_staff_rental_procedure_with_contact.php)
- [Rental-close procedure migration](my-project/database/migrations/2026_10_25_120000_create_package_rate_close_rental_procedure.php)

### Bike ID activity-log migration pending

The configured database still has the older `sp_open_rental_with_contact` procedure, which records the internal numeric bike key in the activity-log Bike ID field. Migration [2026_11_22_000002_fix_rental_activity_log_bike_id.php](my-project/database/migrations/2026_11_22_000002_fix_rental_activity_log_bike_id.php) was reported as pending. It was not applied during this audit.

### Database aggregate checks

The following read-only aggregate checks returned zero: orphan rental references to bikes, borrowers, or staff; bikes with multiple active rentals; rental/bike status mismatches; rentals with multiple payment or overtime rows; and open maintenance issues whose bike was not marked for repair.

Foreign keys and unique indexes for admin/staff usernames and emails, bicycle QR codes, and pricing type/duration pairs were present. Pricing package rows existed for the configured bike types; price amounts were not audited.

### Test and validation status

- Full suite: 83 tests — 14 passed, 1 failed, 68 errors.
- The test environment is configured for in-memory SQLite in [phpunit.xml](my-project/phpunit.xml), but the PHP runtime does not have `pdo_sqlite`. This blocks the database-backed feature tests.
- The remaining failure is [ExampleTest.php](my-project/tests/Feature/ExampleTest.php), which expects `/` to return HTTP 200 while the application redirects to login (HTTP 302).
- The 3 unit tests passed. PHP syntax checks, route listing (45 routes), and `composer validate` passed.
- Browser/E2E flows were not run.

### Demo readiness and logs

QR scanning, charts, and exports use CDN-hosted scripts. The scanner includes a manual-entry fallback, but behavior with blocked or unavailable CDNs was not browser-tested.

The local Laravel log contained 141 ERROR entries, with the latest timestamp `2026-11-26 13:16:05`. Only counts and timestamps were inspected; log messages were not reviewed, so these entries remain untriaged.

## Recommended priorities

1. Add object-level authorization before returning borrower ID documents.
2. Revoke active sessions when staff/admin passwords are reset.
3. Make rental open/close state checks atomic and enforce appropriate database invariants.
4. Review and schedule the pending bike-ID stored-procedure migration after confirming deployment readiness.
5. Enable the SQLite PDO driver or configure a supported test database, then resolve the stale root-route expectation and rerun the full suite.
6. Triage the local application errors and validate CDN-dependent flows in the intended demo environment.
