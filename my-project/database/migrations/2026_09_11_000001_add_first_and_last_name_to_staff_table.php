<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('first_name', 75)->nullable()->after('full_name');
            $table->string('last_name', 75)->nullable()->after('first_name');
        });

        DB::table('staff')->select('staff_id', 'full_name')->orderBy('staff_id')->each(function ($staff) {
            $name = trim($staff->full_name);
            $separator = strpos($name, ' ');

            DB::table('staff')->where('staff_id', $staff->staff_id)->update([
                'first_name' => $separator === false ? $name : substr($name, 0, $separator),
                'last_name' => $separator === false ? '' : trim(substr($name, $separator + 1)),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};