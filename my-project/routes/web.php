<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Staff\InventoryController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth routes
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Staff routes (protected)
Route::get('/staff', [InventoryController::class, 'index'])->name('staff.home');
Route::post('/staff/inventory', [InventoryController::class, 'store'])->name('staff.inventory.store');
Route::patch('/staff/inventory/{bike}/toggle-status', [InventoryController::class, 'toggleStatus'])->name('staff.inventory.toggle-status');

// Admin routes (protected)
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/bikes', [DashboardController::class, 'storeBike'])->name('admin.bikes.store');
});