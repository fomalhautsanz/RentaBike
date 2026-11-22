<?php

use App\Models\Admin;
use App\Models\Staff;
use App\Support\RentalPricing;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('activity_log');
    Schema::dropIfExists('rental_price_settings');
    Schema::dropIfExists('staff');
    Schema::dropIfExists('admin');

    Schema::create('admin', function (Blueprint $table) {
        $table->increments('admin_id');
        $table->string('username')->unique();
        $table->string('full_name');
        $table->string('email');
        $table->string('email_hash')->nullable();
        $table->string('password_hash');
    });

    Schema::create('staff', function (Blueprint $table) {
        $table->increments('staff_id');
        $table->unsignedInteger('admin_id');
        $table->string('username')->unique();
        $table->string('password_hash');
        $table->string('full_name');
        $table->string('first_name');
        $table->string('last_name');
        $table->string('email');
        $table->string('email_hash')->nullable();
        $table->text('permissions')->nullable();
        $table->string('role');
        $table->string('status');
    });

    Schema::create('rental_price_settings', function (Blueprint $table) {
        $table->id();
        $table->string('bike_type', 50);
        $table->unsignedSmallInteger('duration_minutes');
        $table->decimal('price', 10, 2);
        $table->timestamps();
        $table->unique(['bike_type', 'duration_minutes']);
    });

    Schema::create('activity_log', function (Blueprint $table) {
        $table->increments('log_id');
        $table->integer('user_id');
        $table->string('user_type');
        $table->string('action');
        $table->dateTime('timestamp')->useCurrent();
        $table->text('details')->nullable();
    });

    $admin = Admin::create([
        'username' => 'owner',
        'full_name' => 'Owner Admin',
        'email' => 'owner@example.test',
        'password_hash' => 'hashed-password',
    ]);

    $this->operator = Staff::create([
        'admin_id' => $admin->admin_id,
        'username' => 'operator',
        'password_hash' => 'hashed-password',
        'full_name' => 'Pricing Operator',
        'first_name' => 'Pricing',
        'last_name' => 'Operator',
        'email' => 'operator@example.test',
        'role' => 'Admin',
        'status' => 'active',
        'permissions' => [],
    ]);

    DB::table('rental_price_settings')->insert([
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
    ]);
});

test('pricing changes require Manage Pricing and immediately update rental rates', function () {
    $payload = [
        'hourly_rates' => [
            'city_bike' => '42.50',
            'lady_mens_bike' => '30',
            'mountain_bike' => '50',
            'sidecar_bike' => '100',
            'road_bike' => '60',
            'childrens_bike' => '20',
            'standard' => '30',
        ],
        'scooter_packages' => ['15' => '50', '30' => '100', '60' => '225'],
    ];

    $this->actingAs($this->operator, 'staff_admin')
        ->post(route('admin.pricing.update'), $payload)
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHasErrors('permission');

    $this->operator->permissions = ['Manage Pricing'];
    $this->operator->save();

    $this->actingAs($this->operator, 'staff_admin')
        ->post(route('admin.pricing.update'), $payload)
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('success');

    expect(RentalPricing::priceFor('City Bike', 60))->toEqual(42.5)
        ->and(RentalPricing::hourlyRate('City Bike'))->toEqual(42.5)
        ->and(RentalPricing::priceFor('E-Scooter', 60))->toEqual(225.0)
        ->and(DB::table('activity_log')->where('action', 'UPDATE_PRICING')->exists())->toBeTrue();
});
