<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin', function (Blueprint $table) {
            $table->increments('admin_id');
            $table->string('username', 100)->unique();
            $table->string('password_hash', 255);
            $table->string('full_name', 150);
            $table->string('email', 150)->unique();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin');
    }
};
