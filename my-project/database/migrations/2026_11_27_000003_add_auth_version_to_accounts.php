<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->unsignedInteger('auth_version')->default(1);
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->unsignedInteger('auth_version')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->dropColumn('auth_version');
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('auth_version');
        });
    }
};
