<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->increments('report_id');
            $table->unsignedInteger('admin_id');
            $table->string('report_type', 100);
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_rentals')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0.00);
            $table->dateTime('generated_at')->useCurrent();

            $table->foreign('admin_id')
                  ->references('admin_id')->on('admin')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->index('admin_id', 'idx_reports_admin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
