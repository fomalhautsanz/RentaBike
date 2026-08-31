<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Staff;

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

        $staff = Staff::orderBy('staff_id')->get()->map(function ($member) {
            return (object) [
                'id'          => $member->staff_id,
                'name'        => $member->full_name,
                'full_name'   => $member->full_name,
                'email'       => $member->email ?? 'N/A',
                'phone'       => $member->phone ?? 'N/A',
                'role'        => $member->role ?? 'Staff',
                'status'      => $member->status ?? 'Active',
                'permissions' => $member->permissions ?? ['View Inventory', 'Process Rentals', 'View Reports'],
            ];
        });

        $admins = Admin::orderBy('admin_id')->get()->map(function ($admin) {
            return (object) [
                'id'          => $admin->admin_id,
                'name'        => $admin->full_name,
                'full_name'   => $admin->full_name,
                'email'       => $admin->email ?? 'N/A',
                'phone'       => $admin->phone ?? 'N/A',
                'role'        => 'Administrator',
                'status'      => 'Active',
                'permissions' => ['Manage Staff', 'View Reports', 'Process Rentals', 'View Inventory'],
            ];
        });

        $staff = $staff->merge($admins);

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