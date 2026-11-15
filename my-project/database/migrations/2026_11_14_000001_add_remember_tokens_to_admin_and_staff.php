<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Remember me" support.
 * - remember_token: Laravel guards (admin portal: admin table ug Admin-role staff)
 * - portal_remember_token: staff app (sha256 hash ra ang gi-save, dili ang token mismo)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            if (! Schema::hasColumn('admin', 'remember_token')) {
                $table->string('remember_token', 100)->nullable();
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            if (! Schema::hasColumn('staff', 'remember_token')) {
                $table->string('remember_token', 100)->nullable();
            }
            if (! Schema::hasColumn('staff', 'portal_remember_token')) {
                $table->string('portal_remember_token', 64)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['remember_token', 'portal_remember_token']);
        });
    }
};
