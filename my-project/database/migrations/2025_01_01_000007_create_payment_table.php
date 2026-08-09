<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment', function (Blueprint $table) {
            $table->increments('payment_id');
            $table->unsignedInteger('rental_id');
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_status', 30)->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->string('digital_receipt_ref', 255)->nullable();

            $table->foreign('rental_id')
                  ->references('rental_id')->on('rental')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->index('rental_id', 'idx_payment_rental');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};
