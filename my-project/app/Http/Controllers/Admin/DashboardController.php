<?php

// hoy gi usab nako ni 
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;   // model sa activity log table 
use App\Models\Admin;         // model sa admin table
use App\Models\Bicycle;       // model sa bicycle table
use App\Models\IssueReport;   // model sa issue report table
use App\Models\Rental;        // model sa rental table
use App\Models\Staff;         // model sa staff table 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // para sa pag-handle og dates/time

class DashboardController extends Controller
{
    // kini ang function nga ma-run pag adto ka sa admin dashboard
    public function index()
{
    $stats = [
        'total_bikes'       => Bicycle::count(),
        'active_rentals'    => Rental::where('status', 'active')->count(),
        'under_maintenance' => Bicycle::whereIn('status', ['maintenance', 'repair'])->count(),
        'revenue'           => (float) Rental::where('status', 'completed')
            ->whereMonth('start_time', now()->month)
            ->whereYear('start_time', now()->year)
            ->sum('total_fee'),
    ];

    // ── Call the helper methods ──────────────────────────────────────────
    $weeklyRentals      = $this->weeklyRentalCounts();
    $revenueVsRentals   = $this->monthlyRevenueVsRentals();
    $peakHours          = $this->peakRentalHours();

    // ── Recent activity from the view ────────────────────────────────────
    $recentActivity = \DB::table('vw_staff_activity_log')
        ->latest('timestamp')
        ->limit(10)
        ->get()
        ->map(function ($log) {
            $log->timestamp = \Carbon\Carbon::parse($log->timestamp);
            return $log;
        });

    // ── Bike type distribution ───────────────────────────────────────────
    $bikeTypeDistribution = Bicycle::selectRaw('bike_type, COUNT(*) as count')
        ->groupBy('bike_type')
        ->pluck('count', 'bike_type')
        ->toArray();

    $staff = Staff::orderBy('staff_id')->get()->map(function ($member) {
        return (object) [
            'id'          => $member->staff_id,
            'name'        => $member->full_name,
            'email'       => $member->email ?? 'N/A',
            'phone'       => $member->phone ?? 'N/A',
            'role'        => $member->role ?? 'Staff',
            'status'      => ucfirst(strtolower($member->status ?? 'active')),
            'permissions' => $this->normalizePermissions($member->permissions ?? null),
        ];
    });

    $admins = Admin::orderBy('admin_id')->get()->map(function ($admin) {
        return (object) [
            'id'          => $admin->admin_id,
            'name'        => $admin->full_name,
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

    $pendingReports    = IssueReport::where('status', 'pending')->count();
    $inProgressReports = IssueReport::where('status', 'in_progress')->count();
    $resolvedReports   = IssueReport::where('status', 'resolved')->count();

    return view('admin.dashboard', compact(
        'staff',
        'stats',
        'pendingReports', 'inProgressReports', 'resolvedReports',
        'recentActivity', 'bikeTypeDistribution',
        'weeklyRentals', 'revenueVsRentals', 'peakHours'
    ));
}

    public function storeBike(Request $request)
    {
        $validated = $request->validate([
            'qr_code' => ['required', 'string', 'max:100', 'unique:bicycle,qr_code'],
            'model' => ['required', 'string', 'max:100'],
            'make' => ['required', 'string', 'max:100'],
            'bike_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:30'],
            'condition' => ['nullable', 'string', 'max:30'],
        ]);

        Bicycle::create([
            ...$validated,
            'bike_type' => $validated['bike_type'] ?? 'Standard',
            'status' => $validated['status'] ?? 'available',
            'condition' => $validated['condition'] ?? 'good',
        ]);

        return back()->with('success', 'Bike added successfully.');
    }

    public function exportAdminDashboardCsv()
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Bike ID', 'QR Code', 'Model', 'Make', 'Type', 'Status', 'Condition']);

            Bicycle::orderBy('bike_id')->each(function (Bicycle $bike) use ($handle) {
                fputcsv($handle, [
                    $bike->bike_id,
                    $bike->qr_code,
                    $bike->model,
                    $bike->make,
                    $bike->bike_type,
                    $bike->status,
                    $bike->condition,
                ]);
            });

            fclose($handle);
        }, 'admin-dashboard.csv', ['Content-Type' => 'text/csv']);
    }

    private function normalizePermissions(mixed $permissions): array
    {
        if (is_string($permissions)) {
            $decoded = json_decode($permissions, true);
            $permissions = is_array($decoded) ? $decoded : explode(',', $permissions);
        }

        if (!is_array($permissions)) {
            return ['View Inventory', 'Process Rentals', 'View Reports'];
        }

        return array_values(array_filter(array_map(
            static fn ($permission) => is_string($permission) ? trim($permission) : null,
            $permissions
        )));
    }

    // Rental counts for the last 7 days, oldest first.
    protected function weeklyRentalCounts(): array
    {
        // start 6 days ago, sugod sa 12am (start of day)
        $start = now()->subDays(6)->startOfDay();

        // ihap ang rentals per date
        $counts = Rental::selectRaw('DATE(start_time) as day, COUNT(*) as total')
            ->where('start_time', '>=', $start)
            ->groupBy('day')
            ->pluck('total', 'day'); // 'YYYY-MM-DD' ang format dawg

        $labels = [];
        $data = [];   

        // loops from day 1 to 6
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('D'); 
            $data[] = (int) ($counts[$date->format('Y-m-d')] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

   // completed rentals, revenue, and rental count for the last 5 months
    protected function monthlyRevenueVsRentals(): array
    {
        $labels = [];
        $revenue = [];
        $rentalCounts = [];

        // loops 4 months ago up to now 
        for ($i = 4; $i >= 0; $i--) {
            $month = now()->subMonths($i);

            // kuhaon ang sum sa total free and count sa rentals in a yr or month 
            $rows = Rental::where('status', 'completed')
                ->whereMonth('start_time', $month->month)
                ->whereYear('start_time', $month->year)
                ->selectRaw('COALESCE(SUM(total_fee), 0) as revenue, COUNT(*) as total')
                ->first();

            $labels[] = $month->format('M'); 
            $revenue[] = (float) $rows->revenue;
            $rentalCounts[] = (int) $rows->total;
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'rentals' => $rentalCounts];
    }

   // rental counts grouped by 2-hour block, based on sa start 
    protected function peakRentalHours(): array
    {
        // ihap tanan rentals per hr
        $counts = Rental::selectRaw('HOUR(start_time) as hr, COUNT(*) as total')
            ->groupBy('hr')
            ->pluck('total', 'hr');

        // fixed hrs 
        $blocks = [6, 8, 10, 12, 14, 16, 18, 20];
        $labels = [];
        $data = [];

        foreach ($blocks as $hour) {
            $suffix = $hour < 12 ? 'AM' : 'PM';
            $display = $hour > 12 ? $hour - 12 : $hour;
            $labels[] = "{$display}{$suffix}";
            $data[] = (int) ($counts[$hour] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }
}