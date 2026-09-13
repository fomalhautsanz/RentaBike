<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\StaffLoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Staff\InventoryController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;

// Root
Route::get('/', function () {
    return redirect()->route('login');
});

// Admin authentication
Route::get('/admin/login', [LoginController::class, 'showLogin'])
    ->name('login');
Route::post('/admin/login', [LoginController::class, 'login'])
    ->name('admin.login.submit');
Route::post('/admin/logout', [LoginController::class, 'logout'])
    ->name('logout');

// Staff authentication
Route::get('/staff/login', [StaffLoginController::class, 'showLogin'])
    ->name('staff.login');
Route::post('/staff/login', [StaffLoginController::class, 'login'])
    ->name('staff.login.submit');
Route::post('/staff/logout', [StaffLoginController::class, 'logout'])
    ->name('staff.logout');

// Admin protected routes
Route::prefix('admin')->middleware('admin.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'exportAdminDashboardCsv'])
        ->name('admin.dashboard.export');
    Route::post('/bikes', [DashboardController::class, 'storeBike'])->name('admin.bikes.store');
});

// Staff protected routes
Route::prefix('staff')->middleware('staff.auth')->group(function () {
    Route::get('/home', [StaffDashboardController::class, 'index'])->name('staff.home');
    Route::get('/export', [StaffDashboardController::class, 'exportStaffDashboardCsv'])
        ->name('staff.export');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('staff.inventory');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('staff.inventory.store');
    Route::patch('/inventory/{bike}/toggle-status', [InventoryController::class, 'toggleStatus'])
        ->name('staff.inventory.toggle-status');
});