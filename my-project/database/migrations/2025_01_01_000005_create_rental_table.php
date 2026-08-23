<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental', function (Blueprint $table) {
            $table->increments('rental_id');
            $table->unsignedInteger('borrower_id');
            $table->unsignedInteger('bike_id');
            $table->unsignedInteger('staff_id');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->decimal('rental_duration_hrs', 8, 2)->nullable();
            $table->decimal('base_fee', 10, 2)->default(0.00);
            $table->decimal('overtime_fee', 10, 2)->default(0.00);
            $table->decimal('total_fee', 10, 2)->default(0.00);
            $table->string('status', 30)->default('active');

            $table->foreign('borrower_id')
                  ->references('borrower_id')->on('borrower')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('bike_id')
                  ->references('bike_id')->on('bicycle')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('staff_id')
                  ->references('staff_id')->on('staff')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->index('borrower_id', 'idx_rental_borrower');
            $table->index('bike_id', 'idx_rental_bike');
            $table->index('staff_id', 'idx_rental_staff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental');
    }
};
