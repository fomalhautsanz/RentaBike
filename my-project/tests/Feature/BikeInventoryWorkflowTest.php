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
        ->patch(route('admin.bikes.update', $bike), [
            'qr_code' => 'MB-202',
            'model' => 'Trail 300',
            'make' => 'Specialized',
            'bike_type' => 'Mountain Bike',
            'condition' => 'good',
            'current_tab' => 'bikes',
        ])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertDatabaseHas('bicycle', [
        'qr_code' => 'MB-202',
        'model' => 'Trail 300',
        'make' => 'Specialized',
    ]);

    $this->actingAs($admin, 'web')
        ->delete(route('admin.bikes.destroy', $bike->fresh()->qr_code))
        ->assertRedirect(route('admin.dashboard'));

    $this->assertDatabaseMissing('bicycle', ['qr_code' => 'MB-202']);
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
