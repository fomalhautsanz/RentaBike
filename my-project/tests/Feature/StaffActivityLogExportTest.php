<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('staff');
    Schema::dropIfExists('admin');
    Schema::dropIfExists('activity_log');

    Schema::create('staff', function (Blueprint $table) {
        $table->increments('staff_id');
        $table->string('full_name');
        $table->string('first_name')->nullable();
        $table->string('last_name')->nullable();
        $table->string('status');
        $table->text('permissions')->nullable();
    });

    Schema::create('admin', function (Blueprint $table) {
        $table->increments('admin_id');
        $table->string('full_name');
    });

    Schema::create('activity_log', function (Blueprint $table) {
        $table->increments('log_id');
        $table->integer('user_id');
        $table->string('user_type');
        $table->string('action');
        $table->dateTime('timestamp');
        $table->text('details')->nullable();
    });

    DB::table('staff')->insert([
        'staff_id' => 7,
        'full_name' => 'Test Staff',
        'first_name' => 'Test',
        'last_name' => 'Staff',
        'status' => 'active',
        'permissions' => '[]',
    ]);

    $this->withSession(['staff_id' => 7]);
});

test('staff activity log export includes only today and resolves actors', function () {
    $today = today()->setTime(10, 30);

    DB::table('activity_log')->insert([
        [
            'log_id' => 1,
            'user_id' => 7,
            'user_type' => 'staff',
            'action' => 'OPEN_RENTAL',
            'timestamp' => $today,
            'details' => 'Rental opened',
        ],
        [
            'log_id' => 2,
            'user_id' => 7,
            'user_type' => 'staff',
            'action' => 'CLOSE_RENTAL',
            'timestamp' => today()->subDay()->setTime(23, 59),
            'details' => 'Rental closed yesterday',
        ],
    ]);

    $this->getJson(route('staff.activity-logs.today'))
        ->assertOk()
        ->assertJsonPath('date', today()->toDateString())
        ->assertJsonCount(1, 'logs')
        ->assertJsonPath('logs.0.log_id', 1)
        ->assertJsonPath('logs.0.actor', 'Test Staff')
        ->assertJsonPath('logs.0.action', 'Open Rental');
});

test('staff activity log export requires an active staff session', function () {
    $this->flushSession()
        ->get(route('staff.activity-logs.today'))
        ->assertRedirect(route('staff.login'));
});
