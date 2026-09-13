<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('admin', function (Blueprint $table) {
        $table->string('email_hash', 64)->nullable()->after('email');
        $table->index('email_hash');
    });

    Schema::table('staff', function (Blueprint $table) {
        $table->string('email_hash', 64)->nullable()->after('email');
        $table->index('email_hash');
    });
}

public function down(): void
{
    Schema::table('admin', function (Blueprint $table) {
        $table->dropColumn('email_hash');
    });
    Schema::table('staff', function (Blueprint $table) {
        $table->dropColumn('email_hash');
    });
}
};
