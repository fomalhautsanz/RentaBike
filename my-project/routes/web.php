<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\StaffLoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\InventoryController;
use App\Http\Controllers\Staff\RentalController;
use App\Http\Controllers\Staff\ScanController;


// ======================================================
// ROOT
// ======================================================

Route::get('/', function () {
    return redirect()->route('login');
});


// ======================================================
// ADMIN AUTHENTICATION
// ======================================================

Route::get('/admin/login', [LoginController::class, 'showLogin'])
    ->name('login');

Route::post('/admin/login', [LoginController::class, 'login'])
    ->name('admin.login.submit');

Route::post('/admin/logout', [LoginController::class, 'logout'])
    ->name('logout');


// ======================================================
// STAFF AUTHENTICATION
// ======================================================

Route::get('/staff/login', [StaffLoginController::class, 'showLogin'])
    ->name('staff.login');

Route::post('/staff/login', [StaffLoginController::class, 'login'])
    ->name('staff.login.submit');

Route::post('/staff/logout', [StaffLoginController::class, 'logout'])
    ->name('staff.logout');



// ======================================================
// ADMIN PROTECTED ROUTES
// ======================================================

Route::prefix('admin')->middleware('admin.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('admin.dashboard');

    Route::post('/bikes', [DashboardController::class, 'storeBike'])
        ->name('admin.bikes.store');
    Route::get('/bikes/next-code', [DashboardController::class, 'nextBikeCode'])
        ->name('admin.bikes.next-code');
    Route::patch('/bikes/{bike}', [DashboardController::class, 'updateBike'])
        ->name('admin.bikes.update');
    Route::post('/bikes/{bike}/verify', [DashboardController::class, 'verifyBikeAction'])
        ->name('admin.bikes.verify');
    Route::delete('/bikes/{bike}', [DashboardController::class, 'destroyBike'])
        ->name('admin.bikes.destroy');

    Route::post('/staff', [DashboardController::class, 'storeStaff'])
        ->name('admin.staff.store');

    Route::post('/staff/{staff}/verify', [DashboardController::class, 'verifyStaffAction'])
        ->name('admin.staff.verify');

    Route::patch('/staff/{staff}', [DashboardController::class, 'updateStaff'])
        ->name('admin.staff.update');

    Route::delete('/staff/{staff}', [DashboardController::class, 'destroyStaff'])
        ->name('admin.staff.destroy');
});


// ======================================================
// STAFF PROTECTED ROUTES
// ======================================================

Route::prefix('staff')
    ->middleware('staff.auth')
    ->group(function () {

        Route::get('/home', [StaffDashboardController::class, 'index'])
            ->name('staff.home');

        Route::get('/export', [StaffDashboardController::class, 'exportStaffDashboardCsv'])
            ->name('staff.export');

        Route::get('/inventory', [InventoryController::class, 'index'])
            ->name('staff.inventory');

        Route::post('/inventory', [InventoryController::class, 'store'])
            ->name('staff.inventory.store');
        Route::get('/inventory/next-code', [InventoryController::class, 'nextBikeCode'])
            ->name('staff.inventory.next-code');

        Route::patch('/inventory/{bike}', [InventoryController::class, 'update'])
            ->name('staff.inventory.update');

        Route::delete('/inventory/{bike}', [InventoryController::class, 'destroy'])
            ->name('staff.inventory.destroy');

        Route::patch('/inventory/{bike}/toggle-status', [InventoryController::class, 'toggleStatus'])
            ->name('staff.inventory.toggle-status');

        // QR scan lookup (read-only): GET /staff/scan/{code}
        Route::get('/scan/{code}', [ScanController::class, 'show'])
            ->name('staff.scan');

        // Rentals human ma-scan: i-open, extend, ug end session
        Route::post('/rentals', [RentalController::class, 'store'])
            ->name('staff.rentals.store');

        Route::get('/rentals/{rental}/borrower-id', [RentalController::class, 'showBorrowerId'])
            ->name('staff.rentals.borrower-id');

        Route::post('/rentals/{rental}/extend', [RentalController::class, 'extend'])
            ->name('staff.rentals.extend');

        Route::post('/rentals/{rental}/end', [RentalController::class, 'end'])
            ->name('staff.rentals.end');
    });