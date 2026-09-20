<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('bikes');
    }

    public function down(): void
    {
        // The duplicate table is intentionally not recreated.
    }
};
