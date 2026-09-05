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
        DB::table('bicycle')->updateOrInsert(
            ['qr_code' => 'TEST001'],
            [
                'model' => 'Test Bike',
                'make' => 'Test',
                'bike_type' => 'standard',
                'status' => 'available',
                'condition' => 'good',
                'created_at' => now(),
            ]
        );
    }
}
