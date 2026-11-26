<?php

use App\Models\Admin;
use App\Models\ActivityLog;
use App\Models\Bicycle;
use App\Models\Staff;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('issue_report');
    Schema::dropIfExists('activity_log');
    Schema::dropIfExists('bicycle_qr_sequences');
    Schema::dropIfExists('bicycle');
    Schema::dropIfExists('staff');
    Schema::dropIfExists('admin');

    Schema::create('admin', function (Blueprint $table) {
        $table->increments('admin_id');
        $table->string('username')->unique();
        $table->string('full_name');
        $table->string('email')->unique();
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
        $table->string('phone')->nullable();
        $table->string('profile_picture')->nullable();
        $table->text('permissions')->nullable();
        $table->string('role');
        $table->string('status');
    });

    Schema::create('bicycle', function (Blueprint $table) {
        $table->increments('bike_id');
        $table->string('qr_code', 100)->unique();
        $table->string('model', 100);
        $table->string('make', 100);
        $table->string('bike_type', 50)->default('Standard');
        $table->string('status', 30)->default('available');
        $table->string('condition', 30)->default('good');
        $table->dateTime('created_at')->useCurrent();
    });

    Schema::create('issue_report', function (Blueprint $table) {
        $table->increments('issue_id');
        $table->unsignedInteger('bike_id');
        $table->string('issue_type');
        $table->string('status');
    });

    Schema::create('bicycle_qr_sequences', function (Blueprint $table) {
        $table->string('prefix', 2)->primary();
        $table->unsignedInteger('last_sequence')->default(0);
    });

    Schema::create('activity_log', function (Blueprint $table) {
        $table->increments('log_id');
        $table->integer('user_id');
        $table->string('user_type');
        $table->string('action');
        $table->dateTime('timestamp')->useCurrent();
        $table->text('details')->nullable();
    });
});

test('admin can update and delete bikes from the inventory', function () {
    $admin = Admin::create([
        'username' => 'admin',
        'full_name' => 'Test Admin',
        'email' => 'admin@example.test',
        'password_hash' => Hash::make('secret-password'),
    ]);

    $bike = Bicycle::create([
        'qr_code' => 'MB-101',
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
        'status' => 'available',
        'condition' => 'good',
    ]);

    $this->actingAs($admin, 'web')
        ->post(route('admin.bikes.verify', $bike), [
            'action' => 'edit',
            'password' => 'secret-password',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Password confirmed.');

    $this->actingAs($admin, 'web')
        ->patch(route('admin.bikes.update', $bike), [
            'qr_code' => 'MB-202',
            'model' => 'Trail 300',
            'make' => 'Specialized',
            'bike_type' => 'Mountain Bike',
            'status' => 'repair',
            'condition' => 'good',
            'current_tab' => 'bikes',
        ])
        ->assertRedirect(route('admin.dashboard'));

    // Bike codes are permanent, so the submitted qr_code is ignored.
    $this->assertDatabaseHas('bicycle', [
        'qr_code' => 'MB-101',
        'model' => 'Trail 300',
        'make' => 'Specialized',
        'status' => 'repair',
        'condition' => 'repair',
    ]);

    $this->actingAs($admin, 'web')
        ->post(route('admin.bikes.verify', $bike->fresh()), [
            'action' => 'delete',
            'password' => 'secret-password',
        ])
        ->assertOk();

    $this->actingAs($admin, 'web')
        ->delete(route('admin.bikes.destroy', $bike->fresh()->qr_code))
        ->assertRedirect(route('admin.dashboard'));

    $this->assertDatabaseMissing('bicycle', ['qr_code' => 'MB-101']);
});

test('admin and staff inventory edits share maintenance conditions', function () {
    $admin = Admin::create([
        'username' => 'shared-condition-admin',
        'full_name' => 'Test Admin',
        'email' => 'shared-condition-admin@example.test',
        'password_hash' => Hash::make('secret-password'),
    ]);
    $staff = Staff::create([
        'admin_id' => $admin->admin_id,
        'username' => 'shared-condition-staff',
        'password_hash' => Hash::make('staff-password'),
        'full_name' => 'Staff Member',
        'first_name' => 'Staff',
        'last_name' => 'Member',
        'email' => 'shared-condition-staff@example.test',
        'role' => 'Staff',
        'status' => 'active',
        'permissions' => ['Edit Inventory'],
    ]);
    $bike = Bicycle::create([
        'qr_code' => 'MB-505',
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
        'status' => 'available',
        'condition' => 'good',
    ]);

    $this->actingAs($admin, 'web')
        ->post(route('admin.bikes.verify', $bike), [
            'action' => 'edit',
            'password' => 'secret-password',
        ])
        ->assertOk();

    $this->actingAs($admin, 'web')
        ->patch(route('admin.bikes.update', $bike), [
            'model' => 'Trail 200',
            'make' => 'Trek',
            'bike_type' => 'Mountain Bike',
            'status' => 'repair',
            'condition' => 'other',
            'current_tab' => 'bikes',
        ])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertDatabaseHas('bicycle', [
        'bike_id' => $bike->bike_id,
        'status' => 'repair',
        'condition' => 'other',
    ]);

    $staffSession = $this->withSession(['staff_id' => $staff->staff_id]);
    $staffSession->postJson(route('staff.inventory.verify', $bike->qr_code), [
        'action' => 'edit',
        'password' => 'staff-password',
    ])->assertOk();

    $staffSession->patchJson(route('staff.inventory.update', $bike->qr_code), [
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
        'condition' => 'Other',
    ])->assertOk();

    $this->assertDatabaseHas('bicycle', [
        'bike_id' => $bike->bike_id,
        'status' => 'repair',
        'condition' => 'other',
    ]);
});

test('bike codes use a separate sequence for each bike type', function () {
    Bicycle::create([
        'qr_code' => 'MB-001',
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
    ]);
    Bicycle::create([
        'qr_code' => 'RB-001',
        'model' => 'Road 100',
        'make' => 'Giant',
        'bike_type' => 'Road Bike',
    ]);

    expect(Bicycle::nextQrCode('Mountain Bike'))->toBe('MB-002')
        ->and(Bicycle::nextQrCode('Road Bike'))->toBe('RB-002')
        ->and(Bicycle::nextQrCode('City Bike'))->toBe('CB-001');
});

test('deleted bicycle QR codes are not reused', function () {
    Bicycle::create([
        'qr_code' => 'MB-001',
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
    ]);
    $deletedBike = Bicycle::create([
        'qr_code' => 'MB-002',
        'model' => 'Trail 300',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
    ]);
    DB::table('bicycle_qr_sequences')->insert([
        'prefix' => 'MB',
        'last_sequence' => 2,
    ]);

    $deletedBike->delete();

    expect(Bicycle::reserveNextQrCode('Mountain Bike'))->toBe('MB-003');
});

test('admin bike creation assigns a category-based code', function () {
    $admin = Admin::create([
        'username' => 'bike-admin',
        'full_name' => 'Bike Admin',
        'email' => 'bike-admin@example.test',
        'password_hash' => Hash::make('secret-password'),
    ]);

    Bicycle::create([
        'qr_code' => 'RB-001',
        'model' => 'Road 100',
        'make' => 'Giant',
        'bike_type' => 'Road Bike',
    ]);

    $this->actingAs($admin, 'web')
        ->post(route('admin.bikes.store'), [
            'model' => 'Road 200',
            'make' => 'Trek',
            'bike_type' => 'Road Bike',
            'condition' => 'good',
            'current_tab' => 'bikes',
        ])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertDatabaseHas('bicycle', [
        'qr_code' => 'RB-002',
        'bike_type' => 'Road Bike',
    ]);

    $log = ActivityLog::where('action', 'ADD_BIKE')->firstOrFail();
    expect($log->user_type)->toBe('admin')
        ->and($log->user_id)->toBe($admin->admin_id)
        ->and($log->details)->toContain('Bike ID: RB-002')
        ->toContain('Database ID: 2');
});

test('admin bike type changes assign a new category code', function () {
    $admin = Admin::create([
        'username' => 'bike-type-admin',
        'full_name' => 'Bike Admin',
        'email' => 'bike-type-admin@example.test',
        'password_hash' => Hash::make('secret-password'),
    ]);
    $bike = Bicycle::create([
        'qr_code' => 'MB-001',
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
    ]);
    Bicycle::create([
        'qr_code' => 'CB-001',
        'model' => 'City 100',
        'make' => 'Giant',
        'bike_type' => 'City Bike',
    ]);

    $this->actingAs($admin, 'web')
        ->post(route('admin.bikes.verify', $bike), [
            'action' => 'edit',
            'password' => 'secret-password',
        ])
        ->assertOk();

    $this->actingAs($admin, 'web')
        ->patch(route('admin.bikes.update', $bike), [
            'model' => 'Trail 200',
            'make' => 'Trek',
            'bike_type' => 'City Bike',
            'condition' => 'good',
            'current_tab' => 'bikes',
        ])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertDatabaseHas('bicycle', [
        'bike_id' => $bike->bike_id,
        'qr_code' => 'CB-002',
        'bike_type' => 'City Bike',
    ]);
});

test('staff type changes assign the next code in the new category', function () {
    $admin = Admin::create([
        'username' => 'admin-type-change',
        'full_name' => 'Test Admin',
        'email' => 'admin-type-change@example.test',
        'password_hash' => Hash::make('secret-password'),
    ]);
    $staff = Staff::create([
        'admin_id' => $admin->admin_id,
        'username' => 'staff-type-change',
        'password_hash' => Hash::make('staff-password'),
        'full_name' => 'Staff Member',
        'first_name' => 'Staff',
        'last_name' => 'Member',
        'email' => 'staff-type-change@example.test',
        'role' => 'Staff',
        'status' => 'active',
        'permissions' => ['Edit Inventory'],
    ]);
    $bike = Bicycle::create([
        'qr_code' => 'MB-001',
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
    ]);
    Bicycle::create([
        'qr_code' => 'RB-001',
        'model' => 'Road 100',
        'make' => 'Giant',
        'bike_type' => 'Road Bike',
    ]);

    $this->withSession(['staff_id' => $staff->staff_id])
        ->postJson(route('staff.inventory.verify', $bike->qr_code), [
            'action' => 'edit',
            'password' => 'staff-password',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Password confirmed.');

    $this->withSession(['staff_id' => $staff->staff_id])
        ->patchJson(route('staff.inventory.update', $bike->qr_code), [
            'model' => 'Trail 200',
            'make' => 'Trek',
            'bike_type' => 'Road Bike',
            'condition' => 'Good',
        ])
        ->assertOk()
        ->assertJsonPath('bike.qr_code', 'RB-002');

    $this->assertDatabaseHas('bicycle', [
        'bike_id' => $bike->bike_id,
        'qr_code' => 'RB-002',
        'bike_type' => 'Road Bike',
    ]);

    $log = ActivityLog::where('action', 'UPDATE_BIKE')->firstOrFail();
    expect($log->user_type)->toBe('staff')
        ->and($log->user_id)->toBe($staff->staff_id)
        ->and($log->details)->toContain('Bike ID: RB-002')
        ->toContain('Database ID: ' . $bike->bike_id);
});

test('staff bike edits and deletes require fresh password confirmation', function () {
    $admin = Admin::create([
        'username' => 'staff-bike-owner',
        'full_name' => 'Test Admin',
        'email' => 'staff-bike-owner@example.test',
        'password_hash' => Hash::make('admin-password'),
    ]);
    $staff = Staff::create([
        'admin_id' => $admin->admin_id,
        'username' => 'bike-editor',
        'password_hash' => Hash::make('staff-password'),
        'full_name' => 'Bike Editor',
        'first_name' => 'Bike',
        'last_name' => 'Editor',
        'email' => 'bike-editor@example.test',
        'role' => 'Staff',
        'status' => 'active',
        'permissions' => ['Edit Inventory', 'Delete Inventory'],
    ]);
    $bike = Bicycle::create([
        'qr_code' => 'MB-201',
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
        'status' => 'available',
        'condition' => 'good',
    ]);
    $session = $this->withSession(['staff_id' => $staff->staff_id]);

    $session->patchJson(route('staff.inventory.update', $bike->qr_code), [
        'model' => 'Trail 300',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
        'condition' => 'Good',
    ])->assertUnprocessable();
    $session->deleteJson(route('staff.inventory.destroy', $bike->qr_code))
        ->assertUnprocessable();

    $session->postJson(route('staff.inventory.verify', $bike->qr_code), [
        'action' => 'edit',
        'password' => 'incorrect',
    ])->assertUnprocessable();

    $session->postJson(route('staff.inventory.verify', $bike->qr_code), [
        'action' => 'edit',
        'password' => 'staff-password',
    ])->assertOk();
    $session->deleteJson(route('staff.inventory.destroy', $bike->qr_code))
        ->assertUnprocessable();
    $session->patchJson(route('staff.inventory.update', $bike->qr_code), [
        'model' => 'Trail 300',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
        'condition' => 'Good',
    ])->assertOk();
    $session->patchJson(route('staff.inventory.update', $bike->qr_code), [
        'model' => 'Trail 400',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
        'condition' => 'Good',
    ])->assertUnprocessable();

    $session->postJson(route('staff.inventory.verify', $bike->qr_code), [
        'action' => 'delete',
        'password' => 'staff-password',
    ])->assertOk();
    $session->deleteJson(route('staff.inventory.destroy', $bike->qr_code))
        ->assertOk()
        ->assertJsonPath('deleted', true);

    $this->assertDatabaseMissing('bicycle', ['bike_id' => $bike->bike_id]);
});

test('the renumbering migration gives every existing bike a category code', function () {
    Bicycle::create([
        'qr_code' => 'RB-090',
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
    ]);
    Bicycle::create([
        'qr_code' => 'RB-091',
        'model' => 'City 100',
        'make' => 'Giant',
        'bike_type' => 'City Bike',
    ]);
    Bicycle::create([
        'qr_code' => 'RB-092',
        'model' => 'Test Bike',
        'make' => 'Test',
        'bike_type' => 'standard',
    ]);

    $migration = require base_path('database/migrations/2026_10_25_100000_renumber_bicycle_qr_codes_by_type.php');
    $migration->up();

    expect(Bicycle::orderBy('bike_id')->pluck('qr_code')->all())
        ->toBe(['MB-001', 'CB-001', 'ST-001']);
});

test('bike edit and delete actions reject missing or incorrect password confirmation', function () {
    $admin = Admin::create([
        'username' => 'admin-confirmation',
        'full_name' => 'Test Admin',
        'email' => 'admin-confirmation@example.test',
        'password_hash' => Hash::make('secret-password'),
    ]);

    $bike = Bicycle::create([
        'qr_code' => 'MB-303',
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
        'status' => 'available',
        'condition' => 'good',
    ]);

    $this->actingAs($admin, 'web')
        ->patch(route('admin.bikes.update', $bike), [
            'qr_code' => 'MB-404',
            'model' => 'Trail 400',
            'make' => 'Trek',
            'bike_type' => 'Mountain Bike',
            'condition' => 'good',
            'current_tab' => 'bikes',
        ])
        ->assertSessionHasErrors('bike_confirmation');

    expect($bike->fresh()->qr_code)->toBe('MB-303');

    $this->actingAs($admin, 'web')
        ->post(route('admin.bikes.verify', $bike), [
            'action' => 'delete',
            'password' => 'wrong-password',
        ])
        ->assertUnprocessable();

    $this->actingAs($admin, 'web')
        ->delete(route('admin.bikes.destroy', $bike))
        ->assertSessionHasErrors('bike_confirmation');

    $this->assertDatabaseHas('bicycle', ['qr_code' => 'MB-303']);
});

test('admin bike inventory only renders permitted actions and their column', function () {
    $staff = Staff::create([
        'admin_id' => 1,
        'username' => 'inventory-admin',
        'password_hash' => Hash::make('staff-password'),
        'full_name' => 'Inventory Admin',
        'first_name' => 'Inventory',
        'last_name' => 'Admin',
        'email' => 'inventory-admin@example.test',
        'role' => 'Admin',
        'status' => 'active',
        'permissions' => [],
    ]);

    $bike = (object) [
        'bike_code' => 'MB-001',
        'name' => 'Trail 200',
        'type' => 'Mountain Bike',
        'qr_code' => 'MB-001',
        'status' => 'Available',
        'condition' => 'Good',
        'last_maintenance' => null,
        'overdue' => false,
        'estimated_overtime_fee' => 0,
    ];

    foreach ([
        [[], false, false],
        [['Edit Inventory'], true, false],
        [['Delete Inventory'], false, true],
        [['Edit Inventory', 'Delete Inventory'], true, true],
    ] as [$permissions, $canEdit, $canDelete]) {
        $staff->permissions = $permissions;
        $html = $this->actingAs($staff, 'staff_admin')
            ->view('admin.pages._bikes', ['bikes' => collect([$bike])])
            ->render();

        expect(str_contains($html, '<th>Actions</th>'))->toBe($canEdit || $canDelete)
            ->and(str_contains($html, 'bike-actions-select'))->toBeFalse()
            ->and(str_contains($html, 'Rent bike'))->toBeFalse()
            ->and(str_contains($html, 'onclick="openEditBike('))->toBe($canEdit)
            ->and(str_contains($html, 'onclick="openDeleteBike('))->toBe($canDelete);
    }
});

test('staff inventory renders real edit and delete actions for bike records', function () {
    $admin = Admin::create([
        'username' => 'admin2',
        'full_name' => 'Test Admin',
        'email' => 'admin2@example.test',
        'password_hash' => Hash::make('secret-password'),
    ]);

    $staff = Staff::create([
        'admin_id' => $admin->admin_id,
        'username' => 'staff',
        'password_hash' => Hash::make('staff-password'),
        'full_name' => 'Staff Member',
        'first_name' => 'Staff',
        'last_name' => 'Member',
        'email' => 'staff@example.test',
        'role' => 'Staff',
        'status' => 'active',
        'permissions' => ['Edit Inventory', 'Delete Inventory'],
    ]);

    Bicycle::create([
        'qr_code' => 'CB-001',
        'model' => 'City 100',
        'make' => 'Giant',
        'bike_type' => 'City Bike',
        'status' => 'available',
        'condition' => 'good',
    ]);

    $response = $this->withSession(['staff_id' => $staff->staff_id])
        ->get(route('staff.home'));

    $response->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('staff.inventory.update')
        ->toContain('staff.inventory.destroy')
        ->toContain('_method');
});

test('staff inventory shows Other for an open other maintenance report', function () {
    $admin = Admin::create([
        'username' => 'admin-other-condition',
        'full_name' => 'Test Admin',
        'email' => 'admin-other-condition@example.test',
        'password_hash' => Hash::make('secret-password'),
    ]);

    $staff = Staff::create([
        'admin_id' => $admin->admin_id,
        'username' => 'staff-other-condition',
        'password_hash' => Hash::make('staff-password'),
        'full_name' => 'Staff Member',
        'first_name' => 'Staff',
        'last_name' => 'Member',
        'email' => 'staff-other-condition@example.test',
        'role' => 'Staff',
        'status' => 'active',
        'permissions' => ['View Inventory'],
    ]);

    $bike = Bicycle::create([
        'qr_code' => 'LM-002',
        'model' => 'Ako',
        'make' => 'Trek',
        'bike_type' => "Lady's/Men's Bike",
        'status' => 'repair',
        'condition' => 'good',
    ]);
    DB::table('issue_report')->insert([
        'bike_id' => $bike->bike_id,
        'issue_type' => 'other',
        'status' => 'pending',
    ]);

    $this->withSession(['staff_id' => $staff->staff_id])
        ->get(route('staff.home'))
        ->assertOk()
        ->assertSee('Other');
});

test('QR allocation seeds from historical activity logs', function () {
    DB::table('activity_log')->insert([
        'user_id' => 1,
        'user_type' => 'admin',
        'action' => 'DELETE_BIKE',
        'details' => 'Deleted bicycle | Bike ID: MB-009 | Database ID: 12',
    ]);
    Schema::dropIfExists('bicycle_qr_sequences');

    $migration = require base_path('database/migrations/2026_11_27_000002_create_bicycle_qr_sequences_table.php');
    $migration->up();

    expect(Bicycle::nextQrCode('Mountain Bike'))->toBe('MB-010');
});

test('bike creation and activity logging roll back together', function () {
    $admin = Admin::create([
        'username' => 'atomic-bike-admin',
        'full_name' => 'Bike Admin',
        'email' => 'atomic-bike-admin@example.test',
        'password_hash' => Hash::make('secret-password'),
    ]);

    ActivityLog::creating(function (): void {
        throw new RuntimeException('Simulated activity-log insert failure.');
    });
    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($admin, 'web')->post(route('admin.bikes.store'), [
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
        'condition' => 'good',
        'current_tab' => 'bikes',
    ]))->toThrow(RuntimeException::class, 'Simulated activity-log insert failure.');

    expect(Bicycle::query()->exists())->toBeFalse();
});
