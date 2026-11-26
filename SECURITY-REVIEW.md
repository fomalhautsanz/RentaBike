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

### NEW FEATURE VALIDATION RESULTS

#### 1. Activity Logs — Expanded Audit Coverage

- **Files and structures inspected:** `app/Support/ActivityLogger.php`; `app/Http/Controllers/Admin/DashboardController.php`; `app/Http/Controllers/Staff/InventoryController.php`; `app/Models/ActivityLog.php`; `resources/views/admin/pages/_logs.blade.php`; `activity_log` and `vw_staff_activity_log`.
- **Tests actually executed:** A read-only PHP probe exercised `ActivityLogger::formatChanges` with null/empty values, permission arrays, multiple fields, and a password-change marker. The targeted feature-test command listed under Regression Testing could not initialize its SQLite databases.
- **Expected behavior:** Successful staff/bike changes produce accurate actor and timestamp records; updates contain only changed fields with old/new values; credentials are never logged; failed operations do not get success entries.
- **Actual behavior:** Create/update/deactivate and bike CRUD handlers call the logger after their primary model writes. Staff and bike update handlers capture selected fields before/after; a null phone renders as `(empty)`, permission arrays render as comma-separated values, and password changes render only `(unchanged) -> (changed)`. The probe confirmed those formats and no credential value was included in the tested format.
- **Status:** **FAIL** (code-level defects found; database-backed behavior blocked).
- **Identified defects:**
  - Staff deactivation always logs `active -> inactive`, even though the UI allows an `On Leave` account to be removed; the prior status can therefore be false.
  - Staff profile-picture updates log `(existing or empty) -> (updated)`, not the actual prior/new state.
  - Recent Activity reads the database view, which maps `system` actors to `Unknown`; the full Activity Logs page maps them to `System`. The two interfaces can disagree for system-generated entries.
  - Ordinary account/bike writes and their log inserts are not one transaction. If the audit insert fails after a successful write, the mutation can persist without its log.
- **Recommended fixes:** Capture the actual pre-deactivation status; record a privacy-safe but distinguishable profile-picture change; map system actors consistently in the view; make the business mutation and audit write atomic (or use an outbox).
- **Regression risks:** Permission-array formatting and field labels are consumed as human-readable text in the Activity Logs UI/export. No database-backed create/update/deactivate scenario or failed-write scenario was executed.

#### 2. Staff Attribution — Correct Actor Identification

- **Files and structures inspected:** `app/Support/ActivityLogger.php`; `app/Http/Middleware/EnsureStaffLoggedIn.php`; `app/Http/Middleware/EnsureAdminLoggedIn.php`; `app/Support/AdminAccess.php`; `app/Models/ActivityLog.php`; `vw_staff_activity_log`; Activity Logs and Recent Activity views.
- **Tests actually executed:** No actor-writing test could execute because the feature-test database driver is unavailable. The route and middleware paths were inspected.
- **Expected behavior:** Staff, admin-table users, super admins, and system actions retain their correct user ID/type across requests and sessions.
- **Actual behavior:** Staff portal requests attach the authenticated `Staff` model to the individual request; the logger prefers that request attribute. Admin portal middleware reloads staff-admin users and selects the corresponding guard on each request. Admin-table users are recorded as `admin`; staff-table admins/super admins are recorded by their actual staff ID as `staff`; system fallback records `user_type=system` and `user_id=0`. These are source-level findings only. The Activity Logs UI displays the stored table type (`Staff`) for staff-table Admin/Super Admin accounts rather than their role, despite the SQL view exposing `actor_role`.
- **Status:** **BLOCKED** for runtime/concurrent-session verification; source review shows request-scoped actor selection, with a presentation inconsistency for system actors recorded above.
- **Identified defects:** No cross-session actor mix-up was reproduced. The system actor display mismatch is confirmed by the view/UI code; concurrent requests and persisted actor IDs were not exercised.
- **Recommended fixes:** Add isolated tests that write logs as each actor type and as concurrent sessions; display actor role consistently when the actor is a staff-table Admin or Super Admin.
- **Regression risks:** Both custom staff sessions and the `staff_admin` guard participate in actor selection. A change to middleware guard selection could affect unrelated admin-page audit actions.

#### 3. Bicycle Identification in Activity Logs

- **Files and structures inspected:** `app/Models/Bicycle.php`; admin and staff inventory handlers in `DashboardController.php` and `InventoryController.php`; `RentalController.php`; the rental-open stored-procedure migrations; `bicycle.qr_code` unique index; `activity_log.details`.
- **Tests actually executed:** Read-only MySQL metadata confirmed the configured database's open-rental procedure still references the numeric `p_bike_id` in its Bike ID log text. The bike-ID correction migration is absent from the applied migrations. Existing QR workflow tests were part of the blocked feature-test run.
- **Expected behavior:** QR code is the Bike ID, Database ID is separate, rental-open logs use the QR code, and log snapshots remain attributable after deletion.
- **Actual behavior:** Admin/staff bike update and deletion details include both `Bike ID: <QR>` and `Database ID: <key>`. Add-bike details contain the QR code in parentheses but omit the Database ID. The checked-in migration defines a QR-based rental-open log, but the configured database is still running the older procedure and migration `2026_11_22_000002_fix_rental_activity_log_bike_id` is pending. Delete details preserve the QR and database key in the log text. The database enforces uniqueness among currently stored QR codes.
- **Status:** **FAIL**.
- **Identified defects:**
  - Rental-open activity logs in the configured database still record the numeric bike key as Bike ID.
  - Add-bike logs do not explicitly record Database ID.
  - `Bicycle::nextQrCode()` starts at current category count + 1. Deleting the highest-numbered code can allow that QR to be reused, so older QR-only logs can later describe a different bicycle. The unique index prevents simultaneous duplicates, not reuse over time.
- **Recommended fixes:** Schedule the pending stored-procedure migration after deployment review; include both identifiers in add logs; allocate immutable, never-reused QR codes (and retain a database-ID snapshot).
- **Regression risks:** QR format and type changes affect scanning, route model binding, inventory, and rental logs. No create/delete/recreate flow or rental-open transaction was executed.

#### 4. Maintenance Status Logs

- **Files and structures inspected:** `app/Http/Controllers/Admin/MaintenanceController.php`; `app/Support/MaintenanceLogs.php`; `app/Http/Controllers/Staff/IssueReportController.php`; maintenance routes; `issue_report`, `bicycle`, `rental`, and `activity_log`; `AdminMaintenanceLogsTest.php`.
- **Tests actually executed:** The test file contains transition, invalid-status, unauthorized-session, last-open-report, active-rental, and other-open-report cases. The targeted feature tests were blocked before setup by missing `pdo_sqlite`; no status was changed in the configured database.
- **Expected behavior:** A changed status logs report code, issue type, bike QR, old/new status, actor, and timestamp; repeated identical status saves do not create duplicate transitions; unauthorized users cannot change status; resolving one report does not release a bike with another open report or active rental.
- **Actual behavior:** Source code logs only when the status differs, formats issue IDs as `RPT-####`, includes issue type, bike QR and old/new labels, and writes the log in the same transaction as the status change. The route is protected by `admin.can:Handle Maintenance`. Availability is withheld for another open issue or active rental. Existing tests assert those core transitions and guards, but did not run here. `previousStatus` is read before the transaction and without a row lock, so concurrent status changes could log a stale transition.
- **Status:** **BLOCKED** for runtime validation.
- **Identified defects:** No sequential defect was reproduced. Concurrency correctness and maintenance restrictions not represented by another open `issue_report` were not verified.
- **Recommended fixes:** Add a row lock and tests for repeated same-status updates, concurrent status changes, admin-staff permission denial, and any non-report maintenance restrictions before releasing a bike.
- **Regression risks:** Resolution updates both `issue_report` and bicycle status/condition. Existing tests should be rerun against MySQL-compatible infrastructure because the configured stored procedures are MySQL-specific.

#### 5. Pricing Management Page

- **Files and structures inspected:** `PricingController.php`; `RentalPricing.php`; `RentalController.php`; `AdminAccess.php`; admin authentication/permission middleware; pricing and staff forms/navigation; `rental_price_settings`, `rental.base_fee`, and the close-rental procedure; `AdminPricingTest.php`, `StaffScanTest.php`, and `AdminStaffVerificationTest.php`.
- **Tests actually executed:** A read-only Laravel validation probe confirmed that the current price rule accepts `0`. The existing pricing, rental-pricing, and staff-role tests were included in the blocked feature-test command. The configured database has 10 pricing rows and currently has no zero-priced rows.
- **Expected behavior:** Only admins with Manage Pricing can view or submit changes; invalid prices are rejected; new rentals use current rates; active/completed rentals retain historical booked-rate calculations.
- **Actual behavior:** The Pricing navigation/page is permission-gated, the POST route requires `Manage Pricing`, `Staff` role permissions are stripped on create/update, and the admin middleware reloads staff-admin users on each request. The form and server validator both set a minimum of zero, and the validator accepts zero. For hourly rentals, `base_fee` and duration preserve the booked effective rate when the configured hourly rate changes. For E-Scooters, however, `RentalPricing::summary()` derives the overtime rate from `base_fee / duration`; the pricing UI says the 60-minute package price is the overtime rate. Because 15/30/60-minute package prices are independently editable, a 15-minute booking can use an overtime rate different from the configured 60-minute package. No per-rental overtime-rate snapshot is stored.
- **Status:** **FAIL**.
- **Identified defects:**
  - Zero price is accepted and can create free rentals/zero overtime despite the requested rejection where a zero price is inappropriate.
  - E-Scooter overtime uses the selected package's effective rate rather than a booking-time snapshot of the 60-minute package rate. Changing package prices independently can therefore violate the page's stated pricing rule.
  - Existing tests verify permission and current rates, but do not cover zero/negative/large values, direct access as a regular Staff account, permission revocation, or an active rental across a rate change.
- **Recommended fixes:** Require a strictly positive price where rentals cannot be free; persist a per-rental overtime-rate snapshot using the intended package rule and consume it at close; add boundary and historical-rental regression tests.
- **Regression risks:** Rental extension adds current package price to `base_fee` and duration, while closure derives an average rate. Define how extension pricing affects the overtime snapshot before changing this calculation.

#### 6. Weekly Rentals Graph

- **Files and structures inspected:** `DashboardController::weeklyRentalCounts()` and `monthlyRevenueVsRentals()`; `_dashboard.blade.php`; `_dashboard_charts.blade.php`; `Rental.start_time`; application timezone configuration; MySQL session/global timezone.
- **Tests actually executed:** A read-only MySQL query found the application timezone is UTC while MySQL `NOW()` uses the system timezone (observed as UTC+08:00). The current-week aggregate was empty and there were no future-dated rentals. A Carbon boundary probe reproduced Monday 04:30 in Manila selecting the previous UTC week (week 47 starting Nov 16) instead of the local Monday week (week 48 starting Nov 23).
- **Expected behavior:** Current business week begins Monday 00:00 and ends Sunday; week/date range and hover date match that week; zero days are present; future records are not shown.
- **Actual behavior:** The controller builds all seven Monday–Sunday labels, a week number/date-range string, and a full-date array used by the chart tooltip. The `whereBetween` query includes the entire Sunday and has no `start_time <= now` condition. It counts every rental status. The graph uses PHP `now()` in UTC, while MySQL-stored start times use the database system timezone; the boundary probe confirms the local Monday mismatch. Charts are calculated on page load and have no week-rollover refresh.
- **Status:** **FAIL**.
- **Identified defects:**
  - The UTC/application versus local-MySQL timestamp mismatch causes the displayed week to remain the previous week during the first eight hours of Monday in Manila; month-based charts using PHP `now()` share the timezone risk.
  - Future-dated rentals within the current week would be counted because the query runs through Sunday without an upper bound at the present time. None existed in the checked database.
  - All statuses are counted; the intended status inclusion rule was not specified or runtime-verified.
- **Recommended fixes:** Use one explicit business timezone for stored and queried rental times; add tests around local Monday 00:00 and Sunday end; cap at the present time if future records must not count; define status inclusion and refresh behavior.
- **Regression risks:** Timezone correction affects weekly/monthly reports and rental timestamps. The current week had no data, so real boundary inclusivity, zero-day chart rendering, tooltip hover, and week rollover remain unverified.

#### 7. Regression Testing

- **Files and structures inspected:** Recent-feature controllers, models, middleware, routes, Blade pages/scripts, migrations, relevant feature tests, and configured MySQL metadata.
- **Tests actually executed:** `php artisan test --compact tests/Feature/AdminPricingTest.php tests/Feature/AdminMaintenanceLogsTest.php tests/Feature/StaffScanTest.php tests/Feature/BikeInventoryWorkflowTest.php` exited **2**: 41 tests, 0 passed, 41 errors (`could not find driver`). The failure is the missing `pdo_sqlite` extension required by `phpunit.xml`. The standalone formatting and zero-price probes ran; the MySQL checks were read-only.
- **Expected behavior:** Run isolated CRUD, auth, pricing/history, maintenance, QR, rental/payment/overtime, graph-boundary, and regression flows without altering demo data.
- **Actual behavior:** Docker is unavailable, Node.js 24.18.0 is present, PHP has `pdo_mysql` but no `pdo_sqlite`, and the configured session driver is `database`. Browser flows were not started because requests would write sessions to the configured database; no isolated test database or test credentials were available. No application data or migrations were changed.
- **Status:** **BLOCKED**.
- **Identified defects:** The source-level defects are listed under features 1, 3, 5, and 6. Runtime regressions in staff CRUD, account/session attribution, bike CRUD, payment processing, overtime closure, maintenance UI, chart rendering, and reports are not ruled out.
- **Recommended fixes:** Provide an isolated MySQL-compatible QA database (including stored procedures) or a working SQLite PDO environment for non-MySQL tests, plus synthetic test accounts and browser test access. Rerun the affected suites and browser flows before treating these features as verified.
- **Regression risks:** The most sensitive interactions are pricing edits during active rentals, QR reassignment after bike deletion, changes to staff-admin permissions/session guards, issue resolution changing bike availability, and local-time report boundaries.

## Remediation follow-up

This section records fixes made after the audit. The original findings and test limitations above describe the audit phase and remain the historical baseline.

### Changes made

| Area | Remediation |
|---|---|
| Staff and bicycle audit logs | Wrapped staff and bike writes with their corresponding activity-log writes in database transactions. Deactivation now records the actual previous status; profile-picture changes record previous/replacement filenames; bicycle add/update/delete entries include the QR identifier and separate numeric Database ID. |
| Bicycle QR allocation | Added persistent, per-type QR sequence allocation seeded from existing inventory and historical activity logs, preventing deleted QR codes from being reused. |
| Pricing and rental history | Staff- and admin-opened rentals save their selected overtime hourly rate. Overtime summaries and closure use that snapshot, while older rows with no snapshot retain the prior fallback calculation. Pricing inputs and server validation reject zero or negative prices. |
| Activity display | Recent Activity renders system-generated records with the actor name `System`. |
| Rental reports | Weekly counts use the current Monday–Sunday database-time window and exclude future rentals; the displayed period includes the week number and date range. Monthly reporting uses the same database-time source. |
| Borrower ID authorization | The protected document endpoint now returns a not-found response unless the requester is the staff member recorded on that rental or has an active Admin/Super Admin staff role. |
| Password-reset session revocation | Admin and staff accounts now have an `auth_version`. Successful password resets increment it, clear remember tokens, and cause existing sessions with the previous version to be rejected by the relevant auth middleware. |

### Database deployment and verification

- Applied `2026_11_22_000002_fix_rental_activity_log_bike_id` and the three new migrations for the rental rate snapshot, bicycle QR sequence, and account auth version. `php artisan migrate:status` confirms all four are applied.
- Checked the installed rental-open procedure; its activity-log insertion uses the bicycle QR code rather than the numeric bicycle primary key.
- Confirmed the rental snapshot column and sequence table are present. The sequence table was initialized from existing bicycle and activity-log identifiers.
- No rental, staff, or activity-log records were created or edited during verification.

### Tests and remaining limitations

- PHP syntax checks passed for all modified PHP files, and `git diff --check` reported no whitespace errors.
- Read-only runtime probes confirmed an overtime summary uses a saved rate snapshot and that the weekly report returns seven days with the expected current week/date range.
- The focused Pest suites were attempted: **73 tests, 0 passed, 73 setup errors**, all before assertions because the installed PHP lacks `pdo_sqlite` (`could not find driver`). Therefore CRUD, permission, transaction rollback, rental closure, password-reset session revocation, document authorization, pricing persistence, maintenance, and graph boundary tests remain unverified at runtime.
- Browser flows were not run. The configured session driver uses the database, and no isolated browser/test database was available.
- **Status:** Source fixes and migration deployment are complete, but feature-level runtime validation remains **BLOCKED**. Rerun the 58-test set and browser flows when `pdo_sqlite` or an isolated MySQL test database is available.
