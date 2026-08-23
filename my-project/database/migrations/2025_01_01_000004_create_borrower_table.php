<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrower', function (Blueprint $table) {
            $table->increments('borrower_id');
            $table->string('full_name', 150);
            $table->string('contact_no', 30)->nullable();
            $table->string('id_photo_id', 255)->nullable();
            $table->dateTime('registered_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrower');
    }
};
