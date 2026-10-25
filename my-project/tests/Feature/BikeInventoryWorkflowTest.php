<?php

use App\Models\Admin;
use App\Models\Bicycle;
use App\Models\Staff;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
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
            'condition' => 'good',
            'current_tab' => 'bikes',
        ])
        ->assertRedirect(route('admin.dashboard'));

    // Bike codes are permanent, so the submitted qr_code is ignored.
    $this->assertDatabaseHas('bicycle', [
        'qr_code' => 'MB-101',
        'model' => 'Trail 300',
        'make' => 'Specialized',
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
