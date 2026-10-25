<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_open_rental_with_contact');

        DB::unprepared("
            CREATE PROCEDURE sp_open_rental_with_contact (
                IN  p_full_name    VARCHAR(150),
                IN  p_contact_no   VARCHAR(30),
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

                INSERT INTO payment (rental_id, amount_paid, payment_status, paid_at)
                VALUES (p_rental_id, p_amount_paid, 'pending', v_now);

                UPDATE bicycle
                SET status = 'rented'
                WHERE bike_id = p_bike_id;

                INSERT INTO activity_log (user_id, user_type, action, timestamp, details)
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
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_open_rental_with_contact');
    }
};
