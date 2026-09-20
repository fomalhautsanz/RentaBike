<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bikes') && Schema::hasTable('bicycle')) {
            DB::table('bikes')->orderBy('id')->each(function (object $bike): void {
                $qrCode = $bike->qr_code ?: $bike->bike_code;

                if (!$qrCode || DB::table('bicycle')->where('qr_code', $qrCode)->exists()) {
                    return;
                }

                DB::table('bicycle')->insert([
                    'qr_code' => $qrCode,
                    'model' => $bike->name,
                    'make' => 'RentaBike',
                    'bike_type' => $bike->type,
                    'status' => match (strtolower($bike->status)) {
                        'rented' => 'rented',
                        'available' => 'available',
                        default => 'repair',
                    },
                    'condition' => match (strtolower($bike->condition)) {
                        'good' => 'good',
                        'missing' => 'missing',
                        default => 'repair',
                    },
                    'created_at' => $bike->created_at ?? now(),
                ]);
            });
        }

        Schema::dropIfExists('bikes');
    }

    public function down(): void
    {
        // The duplicate table is intentionally not recreated.
    }
};
