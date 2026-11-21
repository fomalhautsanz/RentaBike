<?php

test('main admin accounts are labeled as super admins with full access', function () {
    $mainAdmin = (object) [
        'id' => 3,
        'name' => 'Test Admin1',
        'first_name' => 'Test',
        'last_name' => 'Admin1',
        'email' => 'admintest@example.com',
        'phone' => 'N/A',
        'role' => 'Admin',
        'status' => 'Active',
        'profile_picture' => null,
        'permissions' => [],
        'is_staff' => false,
        'is_main_admin' => true,
    ];

    $this->view('admin.pages._staff', ['staff' => collect([$mainAdmin])])
        ->assertSee('Super Admin')
        ->assertSee('Full Access')
        ->assertSee('SA-0003')
        ->assertSee('data-role="Super Admin"', false)
        ->assertDontSee('Manage Staff')
        ->assertDontSee('View Inventory');
});

test('staff accounts use a distinct staff id prefix', function () {
    $staff = (object) [
        'id' => 3,
        'name' => 'Bike Staff',
        'first_name' => 'Bike',
        'last_name' => 'Staff',
        'email' => 'bike-staff@example.com',
        'phone' => 'N/A',
        'role' => 'Staff',
        'status' => 'Active',
        'profile_picture' => null,
        'permissions' => [],
        'is_staff' => true,
        'is_main_admin' => false,
    ];

    $this->view('admin.pages._staff', ['staff' => collect([$staff])])
        ->assertSee('STF-0003');
});

test('staff admin accounts use a distinct admin id prefix', function () {
    $staffAdmin = (object) [
        'id' => 3,
        'name' => 'Bike Admin',
        'first_name' => 'Bike',
        'last_name' => 'Admin',
        'email' => 'bike-admin@example.com',
        'phone' => 'N/A',
        'role' => 'Admin',
        'status' => 'Active',
        'profile_picture' => null,
        'permissions' => [],
        'is_staff' => true,
        'is_main_admin' => false,
    ];

    $this->view('admin.pages._staff', ['staff' => collect([$staffAdmin])])
        ->assertSee('ADM-0003');
});

test('staff super admins display full access and a distinct id prefix', function () {
    $superAdmin = (object) [
        'id' => 3,
        'name' => 'Staff Super Admin',
        'first_name' => 'Staff',
        'last_name' => 'Super Admin',
        'email' => 'staff-super-admin@example.com',
        'phone' => 'N/A',
        'role' => 'Super Admin',
        'status' => 'Active',
        'profile_picture' => null,
        'permissions' => [],
        'is_staff' => true,
        'is_super_admin' => true,
    ];

    $this->view('admin.pages._staff', ['staff' => collect([$superAdmin])])
        ->assertSee('Super Admin')
        ->assertSee('Full Access')
        ->assertSee('SADM-0003');
});

test('overflow privilege label exposes the full assigned action list', function () {
    $staff = (object) [
        'id' => 4,
        'name' => 'Bike Staff',
        'first_name' => 'Bike',
        'last_name' => 'Staff',
        'email' => 'bike-staff@example.com',
        'phone' => 'N/A',
        'role' => 'Staff',
        'status' => 'Active',
        'profile_picture' => null,
        'permissions' => ['View Inventory', 'Add Inventory', 'Edit Inventory', 'Delete Inventory'],
        'is_staff' => true,
        'is_main_admin' => false,
    ];

    $this->view('admin.pages._staff', ['staff' => collect([$staff])])
        ->assertSee('+1')
        ->assertSee('title="View Inventory, Add Inventory, Edit Inventory, Delete Inventory"', false);
});
