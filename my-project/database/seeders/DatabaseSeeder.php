<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (DB::table('bicycle')->exists()) {
            return;
        }

        DB::table('bicycle')->insert(
            [
                'qr_code' => 'MB-001',
                'model' => 'Test Bike',
                'make' => 'Test',
                'bike_type' => 'Mountain Bike',
                'status' => 'available',
                'condition' => 'good',
                'created_at' => now(),
            ]
        );
    }
}
