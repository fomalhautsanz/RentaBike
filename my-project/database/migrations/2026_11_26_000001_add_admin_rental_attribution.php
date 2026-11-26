<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE rental MODIFY staff_id INT UNSIGNED NULL');

        Schema::table('rental', function (Blueprint $table) {
            $table->unsignedInteger('admin_id')->nullable()->after('staff_id');
            $table->foreign('admin_id', 'rental_admin_id_foreign')
                ->references('admin_id')
                ->on('admin')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->index('admin_id', 'idx_rental_admin');
        });

        $this->createRentalViews(true);
    }

    public function down(): void
    {
        if (DB::table('rental')->whereNotNull('admin_id')->exists()) {
            throw new RuntimeException('Cannot remove admin rental attribution while admin-created rentals exist.');
        }

        $this->createRentalViews(false);

        Schema::table('rental', function (Blueprint $table) {
            $table->dropForeign('rental_admin_id_foreign');
            $table->dropIndex('idx_rental_admin');
            $table->dropColumn('admin_id');
        });

        DB::statement('ALTER TABLE rental MODIFY staff_id INT UNSIGNED NOT NULL');
    }

    private function createRentalViews(bool $includeAdmin): void
    {
        if ($includeAdmin) {
            DB::unprepared("
                CREATE OR REPLACE VIEW vw_active_rentals AS
                SELECT
                    r.rental_id,
                    r.start_time,
                    TIMESTAMPDIFF(MINUTE, r.start_time, NOW()) AS elapsed_minutes,
                    r.rental_duration_hrs,
                    r.base_fee, r.overtime_fee, r.total_fee,
                    b.borrower_id,
                    b.full_name AS borrower_name,
                    bk.bike_id,
                    bk.qr_code,
                    bk.model AS bike_model,
                    bk.make AS bike_make,
                    s.staff_id,
                    s.full_name AS staff_name,
                    s.role AS staff_role,
                    CASE
                        WHEN TIMESTAMPDIFF(MINUTE, r.start_time, NOW()) > (r.rental_duration_hrs * 60)
                        THEN 'overdue' ELSE 'on_time'
                    END AS overtime_status,
                    r.admin_id,
                    a.full_name AS admin_name
                FROM rental r
                JOIN borrower b ON r.borrower_id = b.borrower_id
                JOIN bicycle bk ON r.bike_id = bk.bike_id
                LEFT JOIN staff s ON r.staff_id = s.staff_id
                LEFT JOIN admin a ON r.admin_id = a.admin_id
                WHERE r.status = 'active'
            ");

            DB::unprepared("
                CREATE OR REPLACE VIEW vw_rental_history AS
                SELECT
                    r.rental_id, r.start_time, r.end_time,
                    r.rental_duration_hrs, r.base_fee, r.overtime_fee, r.total_fee,
                    r.status AS rental_status,
                    b.full_name AS borrower_name,
                    bk.qr_code,
                    bk.model AS bike_model,
                    bk.make AS bike_make,
                    s.full_name AS staff_name,
                    p.payment_id, p.amount_paid, p.payment_status, p.paid_at,
                    p.digital_receipt_ref,
                    ot.overtime_hrs, ot.penalty_fee,
                    r.admin_id,
                    a.full_name AS admin_name
                FROM rental r
                JOIN borrower b ON r.borrower_id = b.borrower_id
                JOIN bicycle bk ON r.bike_id = bk.bike_id
                LEFT JOIN staff s ON r.staff_id = s.staff_id
                LEFT JOIN admin a ON r.admin_id = a.admin_id
                LEFT JOIN payment p ON p.rental_id = r.rental_id
                LEFT JOIN overtime ot ON ot.rental_id = r.rental_id
            ");

            return;
        }

        DB::unprepared("
            CREATE OR REPLACE VIEW vw_active_rentals AS
            SELECT
                r.rental_id,
                r.start_time,
                TIMESTAMPDIFF(MINUTE, r.start_time, NOW()) AS elapsed_minutes,
                r.rental_duration_hrs,
                r.base_fee, r.overtime_fee, r.total_fee,
                b.borrower_id,
                b.full_name AS borrower_name,
                bk.bike_id,
                bk.qr_code,
                bk.model AS bike_model,
                bk.make AS bike_make,
                s.staff_id,
                s.full_name AS staff_name,
                s.role AS staff_role,
                CASE
                    WHEN TIMESTAMPDIFF(MINUTE, r.start_time, NOW()) > (r.rental_duration_hrs * 60)
                    THEN 'overdue' ELSE 'on_time'
                END AS overtime_status
            FROM rental r
            JOIN borrower b ON r.borrower_id = b.borrower_id
            JOIN bicycle bk ON r.bike_id = bk.bike_id
            JOIN staff s ON r.staff_id = s.staff_id
            WHERE r.status = 'active'
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW vw_rental_history AS
            SELECT
                r.rental_id, r.start_time, r.end_time,
                r.rental_duration_hrs, r.base_fee, r.overtime_fee, r.total_fee,
                r.status AS rental_status,
                b.full_name AS borrower_name,
                bk.qr_code,
                bk.model AS bike_model,
                bk.make AS bike_make,
                s.full_name AS staff_name,
                p.payment_id, p.amount_paid, p.payment_status, p.paid_at,
                p.digital_receipt_ref,
                ot.overtime_hrs, ot.penalty_fee
            FROM rental r
            JOIN borrower b ON r.borrower_id = b.borrower_id
            JOIN bicycle bk ON r.bike_id = bk.bike_id
            JOIN staff s ON r.staff_id = s.staff_id
            LEFT JOIN payment p ON p.rental_id = r.rental_id
            LEFT JOIN overtime ot ON ot.rental_id = r.rental_id
        ");
    }
};
