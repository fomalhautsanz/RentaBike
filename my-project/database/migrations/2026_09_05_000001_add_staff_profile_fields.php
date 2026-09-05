<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email_hash');
            $table->string('profile_picture')->nullable()->after('phone');
            $table->json('permissions')->nullable()->after('profile_picture');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['phone', 'profile_picture', 'permissions']);
        });
    }
};
