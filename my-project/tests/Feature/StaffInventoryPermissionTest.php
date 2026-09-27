<?php

use App\Http\Controllers\Staff\InventoryController;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('staff with view inventory but not add inventory cannot create bikes', function () {
    $request = Request::create('/staff/inventory', 'POST');
    $request->attributes->set('staffPermissions', ['View Inventory']);

    expect(fn () => (new InventoryController())->store($request))
        ->toThrow(HttpException::class);
});