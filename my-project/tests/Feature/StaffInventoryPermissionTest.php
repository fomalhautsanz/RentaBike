<?php

use App\Http\Controllers\Staff\InventoryController;
use App\Models\Admin;
use App\Models\Staff;
use App\Support\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('staff with view inventory but not add inventory cannot create bikes', function () {
    $request = Request::create('/staff/inventory', 'POST');
    $request->attributes->set('staffPermissions', ['View Inventory']);

    expect(fn () => (new InventoryController())->store($request))
        ->toThrow(HttpException::class);
});

test('inventory action permissions imply view inventory access', function () {
    foreach (['Add Inventory', 'Edit Inventory', 'Delete Inventory'] as $permission) {
        $staff = new Staff(['permissions' => [$permission]]);

        expect($staff->hasPermission('View Inventory'))->toBeTrue();
    }

    $staff = new Staff(['permissions' => []]);
    expect($staff->hasPermission('View Inventory'))->toBeFalse();
});

test('super admins have admin portal access and bypass individual permission checks', function () {
    $superAdmin = new Staff([
        'role' => 'Super Admin',
        'status' => 'active',
        'permissions' => [],
    ]);

    expect($superAdmin->isSuperAdmin())->toBeTrue()
        ->and($superAdmin->hasAdminPortalAccess())->toBeTrue()
        ->and($superAdmin->hasPermission('Delete Inventory'))->toBeTrue()
        ->and($superAdmin->hasPermission('Unlisted Future Permission'))->toBeTrue();
});

test('only main admins and super admins can manage super admin status', function () {
    Auth::guard('web')->setUser(new Admin());
    expect(AdminAccess::canManageSuperAdmins())->toBeTrue();

    Auth::guard('web')->logout();
    Auth::guard('staff_admin')->setUser(new Staff([
        'role' => 'Admin',
        'status' => 'active',
        'permissions' => ['Manage Staff'],
    ]));
    expect(AdminAccess::canManageSuperAdmins())->toBeFalse();

    Auth::guard('staff_admin')->setUser(new Staff([
        'role' => 'Super Admin',
        'status' => 'active',
        'permissions' => [],
    ]));
    expect(AdminAccess::canManageSuperAdmins())->toBeTrue();
});

test('staff forms group add edit and delete under view inventory', function () {
    $html = view('admin.modals._staff_modals')->render();

    expect(substr_count($html, 'data-inventory-options'))->toBe(2)
        ->and(substr_count($html, 'value="Add Inventory"'))->toBe(2)
        ->and(substr_count($html, 'value="Edit Inventory"'))->toBe(2)
        ->and(substr_count($html, 'value="Delete Inventory"'))->toBe(2)
        ->and($html)->toContain('id="super-admin-confirm-modal"')
        ->and($html)->toContain('Grant Full Access');
});

test('main admins can see explicit super admin promotion buttons', function () {
    Auth::guard('web')->setUser(new Admin());

    $html = view('admin.modals._staff_modals')->render();

    expect($html)->toContain('onclick="makeAddStaffSuperAdmin()"')
        ->and($html)->toContain('onclick="makeEditStaffSuperAdmin()"');
});