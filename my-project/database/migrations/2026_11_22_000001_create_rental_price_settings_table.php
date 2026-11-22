<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_price_settings', function (Blueprint $table) {
            $table->id();
            $table->string('bike_type', 50);
            $table->unsignedSmallInteger('duration_minutes');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['bike_type', 'duration_minutes'], 'rental_price_type_duration_unique');
        });

        $now = now();
        $prices = [
            ['bike_type' => 'City Bike', 'duration_minutes' => 60, 'price' => 30],
            ['bike_type' => "Lady's/Men's Bike", 'duration_minutes' => 60, 'price' => 30],
            ['bike_type' => 'Mountain Bike', 'duration_minutes' => 60, 'price' => 50],
            ['bike_type' => 'Sidecar Bike', 'duration_minutes' => 60, 'price' => 100],
            ['bike_type' => 'Road Bike', 'duration_minutes' => 60, 'price' => 60],
            ['bike_type' => "Children's Bike", 'duration_minutes' => 60, 'price' => 20],
            ['bike_type' => 'Standard', 'duration_minutes' => 60, 'price' => 30],
            ['bike_type' => 'E-Scooter', 'duration_minutes' => 15, 'price' => 50],
            ['bike_type' => 'E-Scooter', 'duration_minutes' => 30, 'price' => 100],
            ['bike_type' => 'E-Scooter', 'duration_minutes' => 60, 'price' => 200],
        ];

        DB::table('rental_price_settings')->insert(array_map(
            fn (array $price): array => [...$price, 'created_at' => $now, 'updated_at' => $now],
            $prices
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_price_settings');
    }
};
