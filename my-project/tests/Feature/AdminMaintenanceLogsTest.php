<?php

use App\Support\MaintenanceLogs;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Schema::dropIfExists('activity_log');
    Schema::dropIfExists('issue_report');
    Schema::dropIfExists('rental');
    Schema::dropIfExists('bicycle');
    Schema::dropIfExists('staff');
    Schema::dropIfExists('admin');

    Schema::create('staff', function (Blueprint $table) {
        $table->increments('staff_id');
        $table->string('full_name');
        $table->string('first_name')->nullable();
        $table->string('last_name')->nullable();
        $table->string('status')->default('active');
        $table->text('permissions')->nullable();
    });

    Schema::create('admin', function (Blueprint $table) {
        $table->increments('admin_id');
        $table->string('username');
        $table->string('password_hash');
        $table->string('full_name');
        $table->string('email');
        $table->string('email_hash')->nullable();
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

    Schema::create('rental', function (Blueprint $table) {
        $table->increments('rental_id');
        $table->unsignedInteger('bike_id');
        $table->string('status');
    });

    Schema::create('issue_report', function (Blueprint $table) {
        $table->increments('issue_id');
        $table->unsignedInteger('bike_id');
        $table->unsignedInteger('staff_id');
        $table->string('issue_type');
        $table->text('description');
        $table->string('photo_ref')->nullable();
        $table->string('status');
        $table->dateTime('reported_at')->useCurrent();
        $table->dateTime('resolved_at')->nullable();
    });

    Schema::create('activity_log', function (Blueprint $table) {
        $table->increments('log_id');
        $table->integer('user_id');
        $table->string('user_type');
        $table->string('action');
        $table->dateTime('timestamp')->useCurrent();
        $table->text('details')->nullable();
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

test('submitting an issue report marks its bike for maintenance', function () {
    $this->withSession(['staff_id' => 7])
        ->postJson(route('staff.issue-reports.store'), [
            'issue_type' => 'damage',
            'bike_code' => 'CB-001',
            'description' => 'Brake needs inspection',
        ])
        ->assertCreated()
        ->assertJsonPath('report.bike_code', 'CB-001');

    expect(DB::table('bicycle')->where('bike_id', 2)->value('status'))->toBe('repair');
});

test('maintenance logs export requires a staff session', function () {
    $this->get(route('staff.maintenance-logs.export'))
        ->assertRedirect(route('staff.login'));
});

test('admin can save a maintenance report status', function () {
    $admin = \App\Models\Admin::forceCreate([
        'admin_id' => 1, 'username' => 'admin', 'password_hash' => 'x', 'full_name' => 'Admin', 'email' => 'a@a.com',
    ]);

    $this->actingAs($admin)
        ->patchJson(route('admin.maintenance.status', 1), ['status' => 'resolved'])
        ->assertOk()
        ->assertJsonPath('report.status', 'Resolved');

    expect(DB::table('issue_report')->where('issue_id', 1)->value('status'))->toBe('resolved')
        ->and(DB::table('issue_report')->where('issue_id', 1)->value('resolved_at'))->not->toBeNull();

    // i-balik sa pending: ma-clear ang resolved_at
    $this->actingAs($admin)
        ->patchJson(route('admin.maintenance.status', 1), ['status' => 'pending'])
        ->assertOk();
    expect(DB::table('issue_report')->where('issue_id', 1)->value('resolved_at'))->toBeNull();

    $this->actingAs($admin)
        ->patchJson(route('admin.maintenance.status', 1), ['status' => 'fixed'])
        ->assertUnprocessable();
});

test('admin can view a staff uploaded issue report photo', function () {
    $admin = \App\Models\Admin::forceCreate([
        'admin_id' => 1, 'username' => 'admin', 'password_hash' => 'x', 'full_name' => 'Admin', 'email' => 'a@a.com',
    ]);
    Storage::fake('local');
    Storage::disk('local')->put('issue-reports/report.png', 'image-bytes');
    DB::table('issue_report')->where('issue_id', 1)->update([
        'photo_ref' => 'issue-reports/report.png',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.maintenance.photo', 1))
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename=report.png');
});

test('saving a report status requires an admin session', function () {
    $this->patchJson(route('admin.maintenance.status', 1), ['status' => 'resolved'])
        ->assertUnauthorized();

    expect(DB::table('issue_report')->where('issue_id', 1)->value('status'))->toBe('pending');
});

test('resolving the last open report sets the bike back to available', function () {
    $admin = \App\Models\Admin::forceCreate([
        'admin_id' => 1, 'username' => 'admin', 'password_hash' => 'x', 'full_name' => 'Admin', 'email' => 'a@a.com',
    ]);
    // bike 1 (repair) naay 2 ka report: #1 pending ug #3 resolved na

    $this->actingAs($admin)
        ->patchJson(route('admin.maintenance.status', 1), ['status' => 'resolved'])
        ->assertOk()
        ->assertJsonPath('bike_released', true);

    $bike = DB::table('bicycle')->where('bike_id', 1)->first();
    expect($bike->status)->toBe('available')->and($bike->condition)->toBe('good');
});

test('resolving a report keeps an actively rented bike rented', function () {
    $admin = \App\Models\Admin::forceCreate([
        'admin_id' => 1, 'username' => 'admin', 'password_hash' => 'x', 'full_name' => 'Admin', 'email' => 'a@a.com',
    ]);
    DB::table('rental')->insert(['rental_id' => 1, 'bike_id' => 1, 'status' => 'active']);

    $this->actingAs($admin)
        ->patchJson(route('admin.maintenance.status', 1), ['status' => 'resolved'])
        ->assertOk()
        ->assertJsonPath('bike_released', false);

    expect(DB::table('bicycle')->where('bike_id', 1)->value('status'))->toBe('rented');
});

test('bike stays under maintenance while another report is still open', function () {
    $admin = \App\Models\Admin::forceCreate([
        'admin_id' => 1, 'username' => 'admin', 'password_hash' => 'x', 'full_name' => 'Admin', 'email' => 'a@a.com',
    ]);
    DB::table('issue_report')->insert([
        'issue_id' => 4, 'bike_id' => 1, 'staff_id' => 7, 'issue_type' => 'damage',
        'description' => 'Loose pedal', 'status' => 'in_progress', 'reported_at' => now(),
    ]);

    $this->actingAs($admin)
        ->patchJson(route('admin.maintenance.status', 1), ['status' => 'resolved'])
        ->assertOk()
        ->assertJsonPath('bike_released', false);

    expect(DB::table('bicycle')->where('bike_id', 1)->value('status'))->toBe('repair');
});
