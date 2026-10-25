<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_close_rental_with_rate');

        DB::unprepared("
            CREATE PROCEDURE sp_close_rental_with_rate (
                IN p_rental_id     INT,
                IN p_staff_id      INT,
                IN p_overtime_rate DECIMAL(10,2)
            )
            BEGIN
                DECLARE v_rental_exists  INT DEFAULT 0;
                DECLARE v_bike_id        INT;
                DECLARE v_borrower_id    INT;
                DECLARE v_start_time     DATETIME;
                DECLARE v_agreed_hrs     DECIMAL(8,2);
                DECLARE v_actual_hrs     DECIMAL(8,4);
                DECLARE v_overtime_hrs   DECIMAL(8,4);
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

                IF p_overtime_rate IS NULL OR p_overtime_rate < 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Overtime rate cannot be negative.';
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

                SELECT bike_id, borrower_id, start_time, rental_duration_hrs, base_fee
                INTO v_bike_id, v_borrower_id, v_start_time, v_agreed_hrs, v_base_fee
                FROM rental
                WHERE rental_id = p_rental_id
                  AND status = 'active'
                LIMIT 1;

                START TRANSACTION;

                SET v_actual_hrs = ROUND(TIMESTAMPDIFF(MINUTE, v_start_time, v_now) / 60.0, 4);
                SET v_overtime_hrs = v_actual_hrs - v_agreed_hrs;

                IF v_overtime_hrs > 0 THEN
                    SET v_penalty_fee = ROUND(CEILING(v_overtime_hrs) * p_overtime_rate, 2);

                    INSERT INTO overtime (rental_id, overtime_hrs, penalty_fee, recorded_at)
                    VALUES (p_rental_id, v_overtime_hrs, v_penalty_fee, v_now);
                END IF;

                SET v_total_fee = v_base_fee + v_penalty_fee;

                UPDATE rental
                SET end_time = v_now,
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

                    SET v_new_paid = v_existing_paid + v_penalty_fee;

                    UPDATE payment
                    SET amount_paid = v_new_paid,
                        payment_status = IF(v_new_paid >= v_total_fee, 'settled', 'pending'),
                        paid_at = v_now
                    WHERE rental_id = p_rental_id;
                ELSE
                    SET v_new_paid = v_penalty_fee;

                    INSERT INTO payment (rental_id, amount_paid, payment_status, paid_at)
                    VALUES (
                        p_rental_id,
                        v_new_paid,
                        IF(v_new_paid >= v_total_fee, 'settled', 'pending'),
                        v_now
                    );
                END IF;

                UPDATE bicycle SET status = 'available' WHERE bike_id = v_bike_id;
                UPDATE borrower SET id_photo_id = NULL WHERE borrower_id = v_borrower_id;

                INSERT INTO activity_log (user_id, user_type, action, timestamp, details)
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
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_close_rental_with_rate');
    }
};
