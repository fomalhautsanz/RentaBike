<?php

use App\Http\Controllers\Staff\InventoryController;
use App\Http\Controllers\Staff\DashboardController;
use App\Models\Bicycle;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('staff without view inventory cannot open inventory', function () {
    $request = Request::create('/staff/inventory', 'GET');
    $request->attributes->set('staffPermissions', []);

    expect(fn () => (new InventoryController())->index($request))
        ->toThrow(HttpException::class);
});

test('staff without view inventory cannot export inventory', function () {
    $request = Request::create('/staff/export', 'GET');
    $request->attributes->set('staffPermissions', []);

    expect(fn () => (new DashboardController())->exportStaffDashboardCsv($request))
        ->toThrow(HttpException::class);
});

test('staff with view inventory but not add inventory cannot create bikes', function () {
    $request = Request::create('/staff/inventory', 'POST');
    $request->attributes->set('staffPermissions', ['View Inventory']);

    expect(fn () => (new InventoryController())->store($request))
        ->toThrow(HttpException::class);
});

test('staff without edit inventory cannot update bikes', function () {
    $request = Request::create('/staff/inventory/BK-001', 'PATCH');
    $request->attributes->set('staffPermissions', ['View Inventory']);

    expect(fn () => (new InventoryController())->update($request, new Bicycle()))
        ->toThrow(HttpException::class);
});

test('staff without delete inventory cannot delete bikes', function () {
    $request = Request::create('/staff/inventory/BK-001', 'DELETE');
    $request->attributes->set('staffPermissions', ['View Inventory']);

    expect(fn () => (new InventoryController())->destroy(request: $request, bike: new Bicycle()))
        ->toThrow(HttpException::class);
});

test('staff cannot delete bikes with an incorrect password', function () {
    $request = Request::create(
        '/staff/inventory/BK-001',
        'DELETE',
        ['password' => 'wrong-password'],
        [],
        [],
        ['HTTP_ACCEPT' => 'application/json']
    );
    $request->attributes->set('staffPermissions', ['Delete Inventory']);
    $request->attributes->set('staffAccount', new Staff(['password_hash' => Hash::make('correct-password')]));

    $response = (new InventoryController())->destroy($request, new Bicycle());

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true)['message'])->toBe('Incorrect password. The bike was not deleted.');
});