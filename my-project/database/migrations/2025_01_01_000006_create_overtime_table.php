<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime', function (Blueprint $table) {
            $table->increments('overtime_id');
            $table->unsignedInteger('rental_id');
            $table->decimal('overtime_hrs', 8, 2)->default(0.00);
            $table->decimal('penalty_fee', 10, 2)->default(0.00);
            $table->dateTime('recorded_at')->useCurrent();

            $table->foreign('rental_id')
                  ->references('rental_id')->on('rental')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            $table->index('rental_id', 'idx_overtime_rental');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime');
    }
};
