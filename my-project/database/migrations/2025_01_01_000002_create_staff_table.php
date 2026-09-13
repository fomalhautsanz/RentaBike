<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->increments('staff_id');
            $table->unsignedInteger('admin_id');
            $table->string('username', 100)->unique();
            $table->string('password_hash', 255);
            $table->string('full_name', 150);
            $table->string('role', 50);
            $table->string('status', 30)->default('active');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('admin_id')
                  ->references('admin_id')->on('admin')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->index('admin_id', 'idx_staff_admin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
