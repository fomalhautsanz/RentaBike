<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_bikes'       => 24,
            'active_rentals'    => 8,
            'under_maintenance' => 3,
            'revenue'           => 19800,
        ];

        $staff    = collect([]);
        $bikes    = collect([]);
        $reports  = collect([]);
        $rentals  = collect([]);

        $pendingReports    = 2;
        $inProgressReports = 1;
        $resolvedReports   = 5;

        return view('admin.dashboard', compact(
            'stats', 'staff', 'bikes', 'reports', 'rentals',
            'pendingReports', 'inProgressReports', 'resolvedReports'
        ));
    }
}