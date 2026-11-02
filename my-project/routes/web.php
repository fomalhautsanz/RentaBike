<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\StaffLoginController;
use App\Http\Controllers\Staff\IssueReportController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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

Route::get('/admin/password/forgot', function () {
    return view('admin.passwords.request');
})->name('admin.password.request');

Route::get('/admin/password/reset', function () {
    return view('admin.passwords.reset');
})->name('admin.password.reset');

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

        Route::get('/home', function () {
            $issueReports = DB::table('issue_report')
                ->join('bicycle', 'issue_report.bike_id', '=', 'bicycle.bike_id')
                ->where('issue_report.staff_id', session('staff_id'))
                ->orderByDesc('issue_report.reported_at')
                ->limit(10)
                ->get([
                    'issue_report.issue_id',
                    'issue_report.issue_type',
                    'issue_report.description',
                    'issue_report.status',
                    'issue_report.reported_at',
                    'bicycle.qr_code',
                ]);

            return view('staff.home', compact('issueReports'));
        })
            ->name('staff.home');

        Route::post('/issues', [IssueReportController::class, 'store'])
            ->name('staff.issues.store');
    });
