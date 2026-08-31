<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_sync_rental_fees_overtime_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_sync_rental_fees_overtime_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_sync_rental_fees_overtime_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_release_bike');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_set_bike_rented');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_validate_bike_availability');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_log_payment_settlement');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_block_staff_delete');

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_manage_staff');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_update_bike_status');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_generate_report');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_close_rental');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_open_rental');

        DB::unprepared('DROP FUNCTION IF EXISTS fn_is_bike_available');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_compute_overtime_penalty');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_compute_rental_fee');
        // ── VIEWS ─────────────────────────────────────────────────────────────

        DB::unprepared("
            CREATE OR REPLACE VIEW vw_active_rentals AS
            SELECT
                r.rental_id,
                r.start_time,
                TIMESTAMPDIFF(MINUTE, r.start_time, NOW()) AS elapsed_minutes,
                r.rental_duration_hrs,
                r.base_fee, r.overtime_fee, r.total_fee,
                b.borrower_id,
                b.full_name      AS borrower_name,
                bk.bike_id,
                bk.qr_code,
                bk.model         AS bike_model,
                bk.make          AS bike_make,
                s.staff_id,
                s.full_name      AS staff_name,
                s.role           AS staff_role,
                CASE
                    WHEN TIMESTAMPDIFF(MINUTE, r.start_time, NOW()) > (r.rental_duration_hrs * 60)
                    THEN 'overdue' ELSE 'on_time'
                END AS overtime_status
            FROM rental r
            JOIN borrower b  ON r.borrower_id = b.borrower_id
            JOIN bicycle  bk ON r.bike_id     = bk.bike_id
            JOIN staff    s  ON r.staff_id    = s.staff_id
            WHERE r.status = 'active'
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW vw_rental_history AS
            SELECT
                r.rental_id, r.start_time, r.end_time,
                r.rental_duration_hrs, r.base_fee, r.overtime_fee, r.total_fee,
                r.status       AS rental_status,
                b.full_name    AS borrower_name,
                bk.qr_code,
                bk.model       AS bike_model,
                bk.make        AS bike_make,
                s.full_name    AS staff_name,
                p.payment_id, p.amount_paid, p.payment_status, p.paid_at,
                p.digital_receipt_ref,
                ot.overtime_hrs, ot.penalty_fee
            FROM rental r
            JOIN borrower b  ON r.borrower_id = b.borrower_id
            JOIN bicycle  bk ON r.bike_id     = bk.bike_id
            JOIN staff    s  ON r.staff_id    = s.staff_id
            LEFT JOIN payment  p  ON p.rental_id  = r.rental_id
            LEFT JOIN overtime ot ON ot.rental_id = r.rental_id
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW vw_bicycle_inventory AS
            SELECT
                bk.bike_id, bk.qr_code, bk.model, bk.make,
                bk.status, bk.`condition`, bk.created_at,
                r.rental_id        AS current_rental_id,
                b.full_name        AS current_borrower,
                r.start_time       AS rental_started_at,
                r.rental_duration_hrs AS agreed_duration_hrs
            FROM bicycle bk
            LEFT JOIN rental   r ON r.bike_id     = bk.bike_id AND r.status = 'active'
            LEFT JOIN borrower b ON b.borrower_id = r.borrower_id
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW vw_revenue_summary AS
            SELECT
                DATE(r.start_time)            AS rental_date,
                COUNT(r.rental_id)            AS total_rentals,
                SUM(r.base_fee)               AS total_base_fees,
                SUM(r.overtime_fee)           AS total_overtime_fees,
                SUM(r.total_fee)              AS total_revenue,
                AVG(r.rental_duration_hrs)    AS avg_duration_hrs,
                COUNT(DISTINCT r.borrower_id) AS unique_borrowers
            FROM rental r
            WHERE r.status = 'completed'
            GROUP BY DATE(r.start_time)
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW vw_staff_activity_log AS
            SELECT
                al.log_id, al.user_id, al.user_type,
                al.action, al.timestamp, al.details,
                CASE al.user_type
                    WHEN 'admin' THEN a.full_name
                    WHEN 'staff' THEN s.full_name
                    ELSE 'Unknown'
                END AS actor_name,
                CASE al.user_type
                    WHEN 'admin' THEN 'Administrator'
                    WHEN 'staff' THEN s.role
                    ELSE 'Unknown'
                END AS actor_role
            FROM activity_log al
            LEFT JOIN admin a ON al.user_type = 'admin' AND al.user_id = a.admin_id
            LEFT JOIN staff s ON al.user_type = 'staff' AND al.user_id = s.staff_id
            ORDER BY al.timestamp DESC
        ");

        // ── FUNCTIONS ─────────────────────────────────────────────────────────

        DB::unprepared("
            CREATE FUNCTION fn_compute_rental_fee (p_duration_hrs DECIMAL(8,2))
            RETURNS DECIMAL(10,2)
            DETERMINISTIC
            BEGIN
                DECLARE v_rate_per_hour DECIMAL(10,2) DEFAULT 60.00;
                DECLARE v_min_hours     DECIMAL(8,2)  DEFAULT 1.00;
                IF p_duration_hrs < v_min_hours THEN
                    SET p_duration_hrs = v_min_hours;
                END IF;
                RETURN ROUND(p_duration_hrs * v_rate_per_hour, 2);
            END
        ");

        DB::unprepared("
            CREATE FUNCTION fn_compute_overtime_penalty (p_overtime_hrs DECIMAL(8,2))
            RETURNS DECIMAL(10,2)
            DETERMINISTIC
            BEGIN
                DECLARE v_penalty_rate DECIMAL(10,2) DEFAULT 30.00;
                IF p_overtime_hrs <= 0 THEN RETURN 0.00; END IF;
                RETURN ROUND(CEILING(p_overtime_hrs) * v_penalty_rate, 2);
            END
        ");

        DB::unprepared("
            CREATE FUNCTION fn_is_bike_available (p_bike_id INT)
            RETURNS TINYINT(1)
            READS SQL DATA
            BEGIN
                DECLARE v_status VARCHAR(30);
                SELECT status INTO v_status FROM bicycle WHERE bike_id = p_bike_id LIMIT 1;
                RETURN IF(v_status = 'available', 1, 0);
            END
        ");

        // ── STORED PROCEDURES ─────────────────────────────────────────────────

        DB::unprepared("
            CREATE PROCEDURE sp_open_rental (
                IN  p_full_name    VARCHAR(150),
		IN p_contact_no VARCHAR(30),
                IN  p_id_photo_ref VARCHAR(255),
                IN  p_bike_id      INT,
                IN  p_staff_id     INT,
                IN  p_duration_hrs DECIMAL(8,2),
                IN  p_amount_paid  DECIMAL(10,2),
                OUT p_rental_id    INT
            )
            BEGIN
                DECLARE v_borrower_id INT;
                DECLARE v_base_fee    DECIMAL(10,2);
                DECLARE v_now         DATETIME DEFAULT NOW();
                DECLARE v_staff_exists INT DEFAULT 0;

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;

                IF p_full_name IS NULL OR TRIM(p_full_name) = '' THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Borrower name is required.';
                END IF;

                IF p_duration_hrs IS NULL OR p_duration_hrs <= 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Rental duration must be greater than zero.';
                END IF;

                IF p_amount_paid IS NULL OR p_amount_paid < 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Payment amount cannot be negative.';
                END IF;

                SELECT COUNT(*)
                INTO v_staff_exists
                FROM staff
                WHERE staff_id = p_staff_id
                AND status = 'active';

                IF v_staff_exists = 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Staff member does not exist or is inactive.';
                END IF;

                IF fn_is_bike_available(p_bike_id) = 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Bicycle is not available for rental.';
                END IF;

                START TRANSACTION;

                INSERT INTO borrower (full_name, contact_no, id_photo_id, registered_at)
		VALUES (p_full_name, p_contact_no, p_id_photo_ref, v_now);

                SET v_borrower_id = LAST_INSERT_ID();

                SET v_base_fee = fn_compute_rental_fee(p_duration_hrs);

                INSERT INTO rental (
                    borrower_id,
                    bike_id,
                    staff_id,
                    start_time,
                    rental_duration_hrs,
                    base_fee,
                    overtime_fee,
                    total_fee,
                    status
                )
                VALUES (
                    v_borrower_id,
                    p_bike_id,
                    p_staff_id,
                    v_now,
                    p_duration_hrs,
                    v_base_fee,
                    0.00,
                    v_base_fee,
                    'active'
                );

                SET p_rental_id = LAST_INSERT_ID();

                INSERT INTO payment (
                    rental_id,
                    amount_paid,
                    payment_status,
                    paid_at
                )
                VALUES (
                    p_rental_id,
                    p_amount_paid,
                    'pending',
                    v_now
                );

                UPDATE bicycle
                SET status = 'rented'
                WHERE bike_id = p_bike_id;

                INSERT INTO activity_log (
                    user_id,
                    user_type,
                    action,
                    timestamp,
                    details
                )
                VALUES (
                    p_staff_id,
                    'staff',
                    'OPEN_RENTAL',
                    v_now,
                    CONCAT(
                        'Rental #', p_rental_id,
                        ' opened for borrower: ', p_full_name,
                        ' | Bike ID: ', p_bike_id,
                        ' | Duration: ', p_duration_hrs,
                        ' hrs | Base Fee: ', v_base_fee
                    )
                );

                COMMIT;
            END
");

        DB::unprepared("
            CREATE PROCEDURE sp_close_rental (
                IN p_rental_id     INT,
                IN p_staff_id      INT,
                IN p_extra_payment DECIMAL(10,2)
            )
            BEGIN
                DECLARE v_rental_exists  INT DEFAULT 0;
                DECLARE v_bike_id        INT;
                DECLARE v_borrower_id    INT;
                DECLARE v_start_time     DATETIME;
                DECLARE v_agreed_hrs     DECIMAL(8,2);
                DECLARE v_actual_hrs     DECIMAL(8,2);
                DECLARE v_overtime_hrs   DECIMAL(8,2);
                DECLARE v_base_fee       DECIMAL(10,2);
                DECLARE v_penalty_fee    DECIMAL(10,2) DEFAULT 0.00;
                DECLARE v_total_fee      DECIMAL(10,2);
                DECLARE v_existing_paid  DECIMAL(10,2) DEFAULT 0.00;
                DECLARE v_new_paid       DECIMAL(10,2) DEFAULT 0.00;
                DECLARE v_now            DATETIME DEFAULT NOW();
                DECLARE v_payment_exists INT DEFAULT 0;

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;

                IF p_extra_payment IS NULL OR p_extra_payment < 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Extra payment cannot be negative.';
                END IF;

                SELECT COUNT(*)
                INTO v_rental_exists
                FROM rental
                WHERE rental_id = p_rental_id
                AND status = 'active';

                IF v_rental_exists = 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'No active rental found with that ID.';
                END IF;

                SELECT
                    bike_id,
                    borrower_id,
                    start_time,
                    rental_duration_hrs,
                    base_fee
                INTO
                    v_bike_id,
                    v_borrower_id,
                    v_start_time,
                    v_agreed_hrs,
                    v_base_fee
                FROM rental
                WHERE rental_id = p_rental_id
                AND status = 'active'
                LIMIT 1;

                START TRANSACTION;

                SET v_actual_hrs =
                    ROUND(
                        TIMESTAMPDIFF(MINUTE, v_start_time, v_now) / 60.0,
                        4
                    );

                SET v_overtime_hrs =
                    v_actual_hrs - v_agreed_hrs;

                IF v_overtime_hrs > 0 THEN
                    SET v_penalty_fee =
                        fn_compute_overtime_penalty(v_overtime_hrs);

                    INSERT INTO overtime (
                        rental_id,
                        overtime_hrs,
                        penalty_fee,
                        recorded_at
                    )
                    VALUES (
                        p_rental_id,
                        v_overtime_hrs,
                        v_penalty_fee,
                        v_now
                    );
                END IF;

                SET v_total_fee =
                    v_base_fee + v_penalty_fee;

                UPDATE rental
                SET
                    end_time = v_now,
                    rental_duration_hrs = v_actual_hrs,
                    overtime_fee = v_penalty_fee,
                    total_fee = v_total_fee,
                    status = 'completed'
                WHERE rental_id = p_rental_id;

                SELECT COUNT(*)
                INTO v_payment_exists
                FROM payment
                WHERE rental_id = p_rental_id;

                IF v_payment_exists > 0 THEN

                    SELECT COALESCE(amount_paid, 0.00)
                    INTO v_existing_paid
                    FROM payment
                    WHERE rental_id = p_rental_id
                    LIMIT 1;

                    SET v_new_paid =
                        v_existing_paid + COALESCE(p_extra_payment, 0.00);

                    UPDATE payment
                    SET
                        amount_paid = v_new_paid,
                        payment_status =
                            CASE
                                WHEN v_new_paid >= v_total_fee
                                THEN 'settled'
                                ELSE 'pending'
                            END,
                        paid_at = v_now
                    WHERE rental_id = p_rental_id;

                ELSE

                    SET v_new_paid =
                        COALESCE(p_extra_payment, 0.00);

                    INSERT INTO payment (
                        rental_id,
                        amount_paid,
                        payment_status,
                        paid_at
                    )
                    VALUES (
                        p_rental_id,
                        v_new_paid,
                        CASE
                            WHEN v_new_paid >= v_total_fee
                            THEN 'settled'
                            ELSE 'pending'
                        END,
                        v_now
                    );

                END IF;

                UPDATE bicycle
                SET status = 'available'
                WHERE bike_id = v_bike_id;

                UPDATE borrower
                SET id_photo_id = NULL
                WHERE borrower_id = v_borrower_id;

                INSERT INTO activity_log (
                    user_id,
                    user_type,
                    action,
                    timestamp,
                    details
                )
                VALUES (
                    p_staff_id,
                    'staff',
                    'CLOSE_RENTAL',
                    v_now,
                    CONCAT(
                        'Rental #', p_rental_id,
                        ' closed | Actual hrs: ', v_actual_hrs,
                        ' | Overtime hrs: ', GREATEST(v_overtime_hrs, 0),
                        ' | Penalty: ', v_penalty_fee,
                        ' | Total Fee: ', v_total_fee,
                        ' | Amount Paid: ', v_new_paid
                    )
                );

                COMMIT;
            END
        ");

     DB::unprepared("
            CREATE PROCEDURE sp_generate_report (
                IN  p_admin_id     INT,
                IN  p_report_type  VARCHAR(100),
                IN  p_period_start DATE,
                IN  p_period_end   DATE,
                OUT p_report_id    INT
            )
            BEGIN
                DECLARE v_total_rentals INT           DEFAULT 0;
                DECLARE v_total_revenue DECIMAL(12,2) DEFAULT 0.00;

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;

                SELECT COUNT(rental_id), IFNULL(SUM(total_fee), 0.00)
                INTO   v_total_rentals, v_total_revenue
                FROM rental WHERE status = 'completed'
                  AND DATE(start_time) BETWEEN p_period_start AND p_period_end;

                START TRANSACTION;

                INSERT INTO reports (admin_id, report_type, period_start, period_end, total_rentals, total_revenue, generated_at)
                VALUES (p_admin_id, p_report_type, p_period_start, p_period_end, v_total_rentals, v_total_revenue, NOW());

                SET p_report_id = LAST_INSERT_ID();

                INSERT INTO activity_log (user_id, user_type, action, timestamp, details)
                VALUES (p_admin_id, 'admin', 'GENERATE_REPORT', NOW(),
                    CONCAT('Report #', p_report_id, ' | Type: ', p_report_type,
                           ' | Period: ', p_period_start, ' to ', p_period_end,
                           ' | Rentals: ', v_total_rentals, ' | Revenue: ', v_total_revenue));

                COMMIT;
            END
        ");


       DB::unprepared("
            CREATE PROCEDURE sp_update_bike_status (
                IN p_bike_id   INT,
                IN p_status    VARCHAR(30),
                IN p_condition VARCHAR(30),
                IN p_user_id   INT,
                IN p_user_type VARCHAR(30)
            )
            BEGIN
                DECLARE v_old_status    VARCHAR(30);
                DECLARE v_old_condition VARCHAR(30);
                DECLARE v_bike_exists   INT DEFAULT 0;
                DECLARE v_active_rental INT DEFAULT 0;

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;

                SELECT COUNT(*)
                INTO v_bike_exists
                FROM bicycle
                WHERE bike_id = p_bike_id;

                IF v_bike_exists = 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Bicycle not found.';
                END IF;

                SELECT status, `condition`
                INTO v_old_status, v_old_condition
                FROM bicycle
                WHERE bike_id = p_bike_id
                LIMIT 1;

                SELECT COUNT(*)
                INTO v_active_rental
                FROM rental
                WHERE bike_id = p_bike_id
                AND status = 'active';

                IF p_status = 'available' AND v_active_rental > 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT =
                            'Cannot mark bicycle as available while it has an active rental.';
                END IF;

                IF p_status = 'rented' AND v_active_rental = 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT =
                            'Cannot mark bicycle as rented without an active rental.';
                END IF;

                START TRANSACTION;

                UPDATE bicycle
                SET
                    status = p_status,
                    `condition` = p_condition
                WHERE bike_id = p_bike_id;

                INSERT INTO activity_log (
                    user_id,
                    user_type,
                    action,
                    timestamp,
                    details
                )
                VALUES (
                    p_user_id,
                    p_user_type,
                    'UPDATE_BIKE_STATUS',
                    NOW(),
                    CONCAT(
                        'Bike ID: ', p_bike_id,
                        ' | Status: ', v_old_status,
                        ' → ', p_status,
                        ' | Condition: ', v_old_condition,
                        ' → ', p_condition
                    )
                );

                COMMIT;
            END
        ");

        DB::unprepared("
            CREATE PROCEDURE sp_manage_staff (
                IN  p_action        VARCHAR(20),
                IN  p_staff_id      INT,
                IN  p_admin_id      INT,
                IN  p_username      VARCHAR(100),
                IN  p_email         VARCHAR(150),
		IN p_email_hash VARCHAR(64),
                IN  p_password_hash VARCHAR(255),
                IN  p_full_name     VARCHAR(150),
                IN  p_role          VARCHAR(50),
                IN  p_status        VARCHAR(30),
                OUT p_out_staff_id  INT
            )
            BEGIN
                IF p_action = 'add' THEN

                    IF p_email IS NULL OR TRIM(p_email) = '' THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Staff email is required.';
                    END IF;

                    INSERT INTO staff (
    admin_id, username, email,
    email_hash,
    password_hash, full_name, role, status
)
VALUES (
    p_admin_id, p_username, p_email,
    p_email_hash,
    p_password_hash, p_full_name, p_role, 'active'
);

                    SET p_out_staff_id = LAST_INSERT_ID();

                    INSERT INTO activity_log (
                        user_id,
                        user_type,
                        action,
                        timestamp,
                        details
                    )
                    VALUES (
                        p_admin_id,
                        'admin',
                        'ADD_STAFF',
                        NOW(),
                        CONCAT(
                            'Added staff: ', p_full_name,
                            ' (', p_username, ')',
                            ' | Email: ', p_email,
                            ' | Role: ', p_role
                        )
                    );

                ELSEIF p_action = 'update' THEN

                    IF p_email IS NULL OR TRIM(p_email) = '' THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Staff email is required.';
                    END IF;

                   UPDATE staff
		SET full_name = p_full_name,
    		email = p_email,
    		email_hash = p_email_hash,
    		password_hash  =
                            IF(
                                p_password_hash IS NOT NULL
                                AND TRIM(p_password_hash) <> '',
                                p_password_hash,
                                password_hash
                            ),
                       role = p_role,
   			status = p_status
			WHERE staff_id = p_staff_id;

                    IF ROW_COUNT() = 0 THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Staff member not found.';
                    END IF;

                    SET p_out_staff_id = p_staff_id;

                    INSERT INTO activity_log (
                        user_id,
                        user_type,
                        action,
                        timestamp,
                        details
                    )
                    VALUES (
                        p_admin_id,
                        'admin',
                        'UPDATE_STAFF',
                        NOW(),
                        CONCAT(
                            'Updated staff ID: ', p_staff_id,
                            ' | Name: ', p_full_name,
                            ' | Email: ', p_email,
                            ' | Role: ', p_role,
                            ' | Status: ', p_status
                        )
                    );

                ELSEIF p_action = 'deactivate' THEN

                    UPDATE staff
                    SET status = 'inactive'
                    WHERE staff_id = p_staff_id;

                    IF ROW_COUNT() = 0 THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Staff member not found.';
                    END IF;

                    SET p_out_staff_id = p_staff_id;

                    INSERT INTO activity_log (
                        user_id,
                        user_type,
                        action,
                        timestamp,
                        details
                    )
                    VALUES (
                        p_admin_id,
                        'admin',
                        'DEACTIVATE_STAFF',
                        NOW(),
                        CONCAT(
                            'Deactivated staff ID: ',
                            p_staff_id
                        )
                    );

                ELSE
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT =
                            'Invalid action. Use: add | update | deactivate';
                END IF;
            END
        ");

        // ── TRIGGERS ──────────────────────────────────────────────────────────

        DB::unprepared("
            CREATE TRIGGER trg_validate_bike_availability
            BEFORE INSERT ON rental FOR EACH ROW
            BEGIN
                DECLARE v_bike_status VARCHAR(30);

                SELECT status
                INTO v_bike_status
                FROM bicycle
                WHERE bike_id = NEW.bike_id
                LIMIT 1;

                IF COALESCE(v_bike_status, '') <> 'available' THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT =
                            'Cannot create rental: bicycle is not available.';
                END IF;
            END
        ");

       DB::unprepared("
            CREATE TRIGGER trg_set_bike_rented
            AFTER INSERT ON rental FOR EACH ROW
            BEGIN
                IF NEW.status = 'active' THEN
                    UPDATE bicycle
                    SET status = 'rented'
                    WHERE bike_id = NEW.bike_id;
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_release_bike
            AFTER UPDATE ON rental FOR EACH ROW
            BEGIN
                IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
                    UPDATE bicycle  SET status = 'available' WHERE bike_id = NEW.bike_id;
                    UPDATE borrower SET id_photo_id = NULL WHERE borrower_id = NEW.borrower_id;
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_sync_rental_fees_overtime_insert
            AFTER INSERT ON overtime FOR EACH ROW
            BEGIN
                UPDATE rental
                SET
                    overtime_fee = (
                        SELECT COALESCE(SUM(penalty_fee), 0.00)
                        FROM overtime
                        WHERE rental_id = NEW.rental_id
                    ),
                    total_fee = base_fee + (
                        SELECT COALESCE(SUM(penalty_fee), 0.00)
                        FROM overtime
                        WHERE rental_id = NEW.rental_id
                    )
                WHERE rental_id = NEW.rental_id;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_sync_rental_fees_overtime_update
            AFTER UPDATE ON overtime FOR EACH ROW
            BEGIN
                UPDATE rental
                SET
                    overtime_fee = (
                        SELECT COALESCE(SUM(penalty_fee), 0.00)
                        FROM overtime
                        WHERE rental_id = NEW.rental_id
                    ),
                    total_fee = base_fee + (
                        SELECT COALESCE(SUM(penalty_fee), 0.00)
                        FROM overtime
                        WHERE rental_id = NEW.rental_id
                    )
                WHERE rental_id = NEW.rental_id;

                IF OLD.rental_id <> NEW.rental_id THEN
                    UPDATE rental
                    SET
                        overtime_fee = (
                            SELECT COALESCE(SUM(penalty_fee), 0.00)
                            FROM overtime
                            WHERE rental_id = OLD.rental_id
                        ),
                        total_fee = base_fee + (
                            SELECT COALESCE(SUM(penalty_fee), 0.00)
                            FROM overtime
                            WHERE rental_id = OLD.rental_id
                        )
                    WHERE rental_id = OLD.rental_id;
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_sync_rental_fees_overtime_delete
            AFTER DELETE ON overtime FOR EACH ROW
            BEGIN
                UPDATE rental
                SET
                    overtime_fee = (
                        SELECT COALESCE(SUM(penalty_fee), 0.00)
                        FROM overtime
                        WHERE rental_id = OLD.rental_id
                    ),
                    total_fee = base_fee + (
                        SELECT COALESCE(SUM(penalty_fee), 0.00)
                        FROM overtime
                        WHERE rental_id = OLD.rental_id
                    )
                WHERE rental_id = OLD.rental_id;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_log_payment_settlement
            AFTER UPDATE ON payment FOR EACH ROW
            BEGIN
                IF NEW.payment_status = 'settled' AND OLD.payment_status != 'settled' THEN
                    INSERT INTO activity_log (user_id, user_type, action, timestamp, details)
                    VALUES (0, 'system', 'PAYMENT_SETTLED', NOW(),
                        CONCAT('Payment #', NEW.payment_id, ' settled for Rental #', NEW.rental_id,
                               ' | Amount: ', NEW.amount_paid));
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_block_staff_delete
            BEFORE DELETE ON staff FOR EACH ROW
            BEGIN
                DECLARE v_rental_count INT;
                SELECT COUNT(*) INTO v_rental_count FROM rental WHERE staff_id = OLD.staff_id;
                IF v_rental_count > 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Cannot delete staff: they have associated rental records. Deactivate instead.';
                END IF;
            END
        ");
    }

    public function down(): void
    {
        // Drop triggers
        DB::unprepared('DROP TRIGGER IF EXISTS trg_block_staff_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_log_payment_settlement');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_sync_rental_fees_overtime_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_sync_rental_fees_overtime_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_release_bike');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_set_bike_rented');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_validate_bike_availability');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_sync_rental_fees_overtime_delete');

        // Drop procedures
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_manage_staff');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_update_bike_status');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_generate_report');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_close_rental');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_open_rental');

        // Drop functions
        DB::unprepared('DROP FUNCTION IF EXISTS fn_is_bike_available');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_compute_overtime_penalty');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_compute_rental_fee');

        // Drop views
        DB::unprepared('DROP VIEW IF EXISTS vw_staff_activity_log');
        DB::unprepared('DROP VIEW IF EXISTS vw_revenue_summary');
        DB::unprepared('DROP VIEW IF EXISTS vw_bicycle_inventory');
        DB::unprepared('DROP VIEW IF EXISTS vw_rental_history');
        DB::unprepared('DROP VIEW IF EXISTS vw_active_rentals');
    }
};
