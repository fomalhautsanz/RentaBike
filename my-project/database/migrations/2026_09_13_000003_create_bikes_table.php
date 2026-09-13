<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bikes', function (Blueprint $table) {
            $table->id();
            $table->string('bike_code')->unique();
            $table->string('name');
            $table->string('type');
            $table->string('qr_code')->unique();
            $table->string('status')->default('Available');
            $table->string('condition')->default('Good');
            $table->date('last_maintenance')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bikes');
    }
};
