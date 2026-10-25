<?php

use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
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

    $admin = Admin::create([
        'username' => 'admin',
        'full_name' => 'Test Admin',
        'email' => 'admin@example.test',
        'password_hash' => Hash::make('correct-password'),
    ]);

    $this->staff = Staff::create([
        'admin_id' => $admin->admin_id,
        'username' => 'staff',
        'password_hash' => Hash::make('staff-password'),
        'full_name' => 'Original Name',
        'first_name' => 'Original',
        'last_name' => 'Name',
        'email' => 'staff@example.test',
        'role' => 'Staff',
        'status' => 'active',
        'permissions' => [],
    ]);

    $this->actingAs($admin);
});

test('staff updates and removals require a correct password confirmation', function () {
    $this->patch(route('admin.staff.update', $this->staff), [
        'first_name' => 'Changed',
        'last_name' => 'Name',
        'email' => 'staff@example.test',
        'phone' => null,
        'role' => 'Staff',
        'status' => 'Active',
    ])->assertSessionHasErrors('staff_confirmation');

    expect($this->staff->fresh()->first_name)->toBe('Original');

    $this->post(route('admin.staff.verify', $this->staff), [
        'action' => 'delete',
        'password' => 'wrong-password',
    ])->assertUnprocessable();

    $this->delete(route('admin.staff.destroy', $this->staff))->assertForbidden();
    expect($this->staff->fresh()->status)->toBe('active');
});

test('a password confirmation is scoped to the selected action and can only be used once', function () {
    $this->post(route('admin.staff.verify', $this->staff), [
        'action' => 'edit',
        'password' => 'correct-password',
    ])->assertOk();

    $this->delete(route('admin.staff.destroy', $this->staff))->assertForbidden();

    $this->patch(route('admin.staff.update', $this->staff), [
        'first_name' => 'Updated',
        'last_name' => 'Name',
        'email' => 'updated@example.test',
        'phone' => '555-0100',
        'role' => 'Staff',
        'status' => 'Active',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHas('success');

    expect($this->staff->fresh()->first_name)->toBe('Updated');
    expect($this->staff->fresh()->email)->toBe('updated@example.test');
    expect($this->staff->fresh()->phone)->toBe('555-0100');
    expect(Hash::check('new-password', $this->staff->fresh()->password_hash))->toBeTrue();

    $this->patch(route('admin.staff.update', $this->staff), [
        'first_name' => 'Second Update',
        'last_name' => 'Name',
        'email' => 'staff@example.test',
        'phone' => null,
        'role' => 'Staff',
        'status' => 'Active',
    ])->assertSessionHasErrors('staff_confirmation');

    expect($this->staff->fresh()->first_name)->toBe('Updated');
});

test('a confirmed removal deactivates the selected staff account', function () {
    $this->post(route('admin.staff.verify', $this->staff), [
        'action' => 'delete',
        'password' => 'correct-password',
    ])->assertOk();

    $this->deleteJson(route('admin.staff.destroy', $this->staff))
        ->assertOk()
        ->assertJsonPath('message', 'Staff member removed successfully.');

    expect($this->staff->fresh()->status)->toBe('inactive');
});

test('staff role cannot receive the Manage Staff permission when created or updated', function () {
    $this->post(route('admin.staff.store'), [
        'first_name' => 'New',
        'last_name' => 'Staff',
        'email' => 'new-staff@example.test',
        'role' => 'Staff',
        'password' => 'staff-password',
        'password_confirmation' => 'staff-password',
        'permissions' => ['Manage Staff', 'View Inventory'],
    ])->assertSessionHas('success');

    $createdStaff = Staff::where('email', 'new-staff@example.test')->firstOrFail();
    expect($createdStaff->permissions)->toBe(['View Inventory']);

    $this->post(route('admin.staff.verify', $this->staff), [
        'action' => 'edit',
        'password' => 'correct-password',
    ])->assertOk();

    $this->patch(route('admin.staff.update', $this->staff), [
        'first_name' => 'Original',
        'last_name' => 'Name',
        'email' => 'staff@example.test',
        'role' => 'Staff',
        'status' => 'Active',
        'permissions' => ['Manage Staff', 'View Inventory'],
    ])->assertSessionHas('success');

    expect($this->staff->fresh()->permissions)->toBe(['View Inventory']);
});

test('admin role can retain the Manage Staff permission', function () {
    $this->post(route('admin.staff.store'), [
        'first_name' => 'New',
        'last_name' => 'Admin',
        'email' => 'new-admin@example.test',
        'role' => 'Admin',
        'password' => 'admin-password',
        'password_confirmation' => 'admin-password',
        'permissions' => ['Manage Staff'],
    ])->assertSessionHas('success');

    $createdAdmin = Staff::where('email', 'new-admin@example.test')->firstOrFail();
    expect($createdAdmin->permissions)->toBe(['Manage Staff']);
});