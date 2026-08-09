<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->increments('log_id');
            $table->integer('user_id'); // not a FK — can be admin, staff, or system (0)
            $table->string('user_type', 30); // admin | staff | system
            $table->string('action', 150);
            $table->dateTime('timestamp')->useCurrent();
            $table->text('details')->nullable();

            $table->index(['user_id', 'user_type'], 'idx_actlog_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
