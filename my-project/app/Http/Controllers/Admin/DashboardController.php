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

    public function exportAdminDashboardCsv()
    {
        $rows = [
            ['metric', 'value'],
            ['total_bikes', 24],
            ['active_rentals', 8],
            ['under_maintenance', 3],
            ['revenue', 19800],
            ['pending_reports', 2],
            ['in_progress_reports', 1],
            ['resolved_reports', 5],
        ];

        return $this->downloadCsv('admin-dashboard-data', $rows);
    }

    public function exportStaffDashboardCsv()
    {
        $rows = [
            ['category', 'bike_id', 'name', 'status'],
            ['Road Bikes', 'RB-001', 'Road Bike', 'Available'],
            ['Road Bikes', 'RB-002', 'Road Bike', 'Rented'],
            ['Road Bikes', 'RB-003', 'Road Bike', 'Repair'],
            ['Sidecar Bikes', 'SC-001', 'Sidecar Bike', 'Available'],
            ['Sidecar Bikes', 'SC-002', 'Sidecar Bike', 'Available'],
            ["Children's Bikes", 'CB-001', 'Kids Bike', 'Available'],
            ["Children's Bikes", 'CB-002', 'Kids Bike', 'Rented'],
            ["Children's Bikes", 'CB-003', 'Kids Bike', 'Available'],
            ["Children's Bikes", 'CB-004', 'Kids Bike', 'Repair'],
            [],
            ['category', 'total bikes', 'available', 'rented', 'repair'],
            ['Road Bikes', 3, 1, 1, 1],
            ['Sidecar Bikes', 2, 2, 0, 0],
            ["Children's Bikes", 4, 2, 1, 1],
        ];

        return $this->downloadCsv('staff-inventory', $rows);
    }

    protected function downloadCsv(string $filename, array $rows)
    {
        $handle = fopen('php://temp', 'w+');

        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', '\\');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '-' . now()->format('Ymd_His') . '.csv"');
    }
}