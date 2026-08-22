<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\StaffLoginController;
use App\Http\Controllers\Admin\DashboardController;


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
});


// ======================================================
// STAFF PROTECTED ROUTES
// ======================================================

Route::prefix('staff')
    ->middleware('staff.auth')
    ->group(function () {

        Route::get('/home', fn () => view('staff.home'))
            ->name('staff.home');
    });