<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_report', function (Blueprint $table) {
            $table->increments('issue_id');
            $table->unsignedInteger('bike_id');
            $table->unsignedInteger('staff_id');
            $table->string('issue_type', 30); // damage | missing | other
            $table->text('description');
            $table->string('photo_ref', 255)->nullable();
            $table->string('status', 30)->default('pending'); // pending | in_progress | resolved
            $table->dateTime('reported_at')->useCurrent();
            $table->dateTime('resolved_at')->nullable();

            $table->foreign('bike_id')
                  ->references('bike_id')->on('bicycle');

            $table->foreign('staff_id')
                  ->references('staff_id')->on('staff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_report');
    }
};
