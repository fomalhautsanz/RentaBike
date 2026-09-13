<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bicycle', function (Blueprint $table) {
            $table->increments('bike_id');
            $table->string('qr_code', 100)->unique();
            $table->string('model', 100);
            $table->string('make', 100);
            $table->string('bike_type', 50)->default('Standard');
            $table->string('status', 30)->default('available');
            $table->string('condition', 30)->default('good');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bicycle');
    }
};
