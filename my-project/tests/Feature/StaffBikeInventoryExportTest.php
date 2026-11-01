<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('staff');
    Schema::dropIfExists('bicycle');

    Schema::create('staff', function (Blueprint $table) {
        $table->increments('staff_id');
        $table->string('full_name');
        $table->string('status');
        $table->text('permissions')->nullable();
    });

    Schema::create('bicycle', function (Blueprint $table) {
        $table->increments('bike_id');
        $table->string('qr_code');
        $table->string('model');
        $table->string('make');
        $table->string('bike_type');
        $table->string('status');
        $table->string('condition');
    });

    DB::table('staff')->insert([
        'staff_id' => 1,
        'full_name' => 'Test Staff',
        'status' => 'active',
        'permissions' => '[]',
    ]);

    DB::table('bicycle')->insert([
        'bike_id' => 42,
        'qr_code' => 'MB-042',
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
        'status' => 'available',
        'condition' => 'good',
    ]);
});

test('staff inventory export returns ordered bike data for a formatted spreadsheet', function () {
    $this->withSession(['staff_id' => 1])
        ->getJson(route('staff.export'))
        ->assertOk()
        ->assertJsonStructure([
            'generated_at',
            'bikes' => [[
                'bike_id',
                'qr_code',
                'model',
                'make',
                'bike_type',
                'status',
                'condition',
            ]],
        ])
        ->assertJsonPath('bikes.0.bike_id', 42)
        ->assertJsonPath('bikes.0.status', 'Available')
        ->assertJsonPath('bikes.0.condition', 'Good');
});

test('staff inventory export requires a staff session', function () {
    $this->get(route('staff.export'))
        ->assertRedirect(route('staff.login'));
});
