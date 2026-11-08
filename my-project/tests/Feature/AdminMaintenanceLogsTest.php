<?php

use App\Support\MaintenanceLogs;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('issue_report');
    Schema::dropIfExists('bicycle');
    Schema::dropIfExists('staff');

    Schema::create('staff', function (Blueprint $table) {
        $table->increments('staff_id');
        $table->string('full_name');
        $table->string('first_name')->nullable();
        $table->string('last_name')->nullable();
        $table->string('status')->default('active');
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

    Schema::create('issue_report', function (Blueprint $table) {
        $table->increments('issue_id');
        $table->unsignedInteger('bike_id');
        $table->unsignedInteger('staff_id');
        $table->string('issue_type');
        $table->text('description');
        $table->string('photo_ref')->nullable();
        $table->string('status');
        $table->dateTime('reported_at');
        $table->dateTime('resolved_at')->nullable();
    });

    DB::table('staff')->insert([
        ['staff_id' => 7, 'full_name' => 'Maria Santos', 'first_name' => 'Maria', 'last_name' => 'Santos', 'permissions' => '["Handle Maintenance"]'],
        ['staff_id' => 8, 'full_name' => 'Juan Cruz', 'first_name' => 'Juan', 'last_name' => 'Cruz', 'permissions' => '["View Inventory"]'],
    ]);

    DB::table('bicycle')->insert([
        ['bike_id' => 1, 'qr_code' => 'MB-001', 'model' => 'Trail 200', 'make' => 'Trek', 'bike_type' => 'Mountain Bike', 'status' => 'repair', 'condition' => 'repair'],
        ['bike_id' => 2, 'qr_code' => 'CB-001', 'model' => 'Cruiser', 'make' => 'Giant', 'bike_type' => 'City Bike', 'status' => 'available', 'condition' => 'good'],
    ]);

    DB::table('issue_report')->insert([
        ['issue_id' => 1, 'bike_id' => 1, 'staff_id' => 7, 'issue_type' => 'damage', 'description' => 'Flat rear tire', 'status' => 'pending', 'reported_at' => now()->subDays(10)],
        ['issue_id' => 2, 'bike_id' => 2, 'staff_id' => 7, 'issue_type' => 'missing', 'description' => 'Not returned after rental', 'status' => 'in_progress', 'reported_at' => now()->subDays(2)],
        ['issue_id' => 3, 'bike_id' => 1, 'staff_id' => 7, 'issue_type' => 'other', 'description' => 'Squeaky brake', 'status' => 'resolved', 'reported_at' => now()->subHours(3)],
    ]);
});

test('maintenance logs come from issue_report, newest first, mapped for the page', function () {
    $rows = MaintenanceLogs::query()->get()->map(fn ($report) => MaintenanceLogs::present($report));

    expect($rows->pluck('report_code')->all())->toBe(['RPT-0003', 'RPT-0002', 'RPT-0001']);

    $first = $rows->firstWhere('report_code', 'RPT-0001');
    expect($first->type)->toBe('Damage')
        ->and($first->status)->toBe('Pending')
        ->and($first->bike_code)->toBe('MB-001')
        ->and($first->bike_name)->toBe('Trail 200 · Trek')
        ->and($first->reporter)->toBe('Maria Santos');

    expect($rows->firstWhere('report_code', 'RPT-0002')->type)->toBe('Missing Bike')
        ->and($rows->firstWhere('report_code', 'RPT-0002')->status)->toBe('In Progress');
});

test('maintenance logs filter by status, type, date and search', function () {
    $codes = fn (array $filters) => MaintenanceLogs::filtered($filters)->get()
        ->map(fn ($report) => MaintenanceLogs::present($report)->report_code)->all();

    expect($codes(['maint_status' => 'pending']))->toBe(['RPT-0001'])
        ->and($codes(['maint_type' => 'missing']))->toBe(['RPT-0002'])
        ->and($codes(['maint_period' => '168']))->toBe(['RPT-0003', 'RPT-0002'])
        ->and($codes(['maint_period' => 'custom', 'maint_from' => now()->subDays(11)->toDateString(), 'maint_to' => now()->subDays(9)->toDateString()]))->toBe(['RPT-0001'])
        ->and($codes(['maint_search' => 'brake']))->toBe(['RPT-0003'])
        ->and($codes(['maint_search' => 'CB-001']))->toBe(['RPT-0002'])
        ->and($codes(['maint_search' => 'RPT-0001']))->toBe(['RPT-0001'])
        ->and($codes(['maint_search' => 'Santos', 'maint_type' => 'damage']))->toBe(['RPT-0001'])
        ->and($codes(['maint_search' => '100%']))->toBe([]);
});

test('staff with Handle Maintenance can export maintenance logs', function () {
    $this->withSession(['staff_id' => 7])
        ->getJson(route('staff.maintenance-logs.export'))
        ->assertOk()
        ->assertJsonCount(3, 'logs')
        ->assertJsonPath('logs.0.report_code', 'RPT-0003')
        ->assertJsonPath('logs.2.type', 'Damage')
        ->assertJsonPath('logs.2.reporter', 'Maria Santos')
        ->assertJsonPath('logs.2.bike_code', 'MB-001');
});

test('staff without Handle Maintenance cannot export maintenance logs', function () {
    $this->withSession(['staff_id' => 8])
        ->getJson(route('staff.maintenance-logs.export'))
        ->assertForbidden();
});

test('maintenance logs export requires a staff session', function () {
    $this->get(route('staff.maintenance-logs.export'))
        ->assertRedirect(route('staff.login'));
});
