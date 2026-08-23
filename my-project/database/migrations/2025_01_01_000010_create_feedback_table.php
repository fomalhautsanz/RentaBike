<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->increments('feedback_id');
            $table->unsignedInteger('borrower_id')->nullable();
            $table->unsignedInteger('rental_id')->nullable();
            $table->text('message');
            $table->string('status', 30)->default('open'); // open | resolved
            $table->dateTime('submitted_at')->useCurrent();

            $table->foreign('borrower_id')
                  ->references('borrower_id')->on('borrower');

            $table->foreign('rental_id')
                  ->references('rental_id')->on('rental');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
