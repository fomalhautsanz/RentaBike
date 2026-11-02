<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Bicycle;
use App\Models\IssueReport;
use App\Models\Rental;
use App\Models\Staff;
use App\Support\RentalPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'available' => Bicycle::where('condition', 'good')->where('status', 'available')->count(),
            'rented'    => Bicycle::where('condition', 'good')->where('status', 'rented')->count(),
            'repair'    => Bicycle::where('condition', '!=', 'good')->count(),
            'total'     => Bicycle::count(),
        ];
        $activeRentals = Rental::with('payment')
            ->where('status', 'active')
            ->get()
            ->keyBy('bike_id');
        $now = RentalPricing::dbNow();
        $addOverdueDetails = static function (Bicycle $bike) use ($activeRentals, $now): Bicycle {
            $rental = $activeRentals->get($bike->bike_id);
            $summary = $rental ? RentalPricing::summary($rental, $bike->bike_type, $now) : null;
            $bike->setAttribute('overdue', $summary['overdue'] ?? false);
            $bike->setAttribute('estimated_overtime_fee', $summary['estimated_overtime_fee'] ?? 0.0);

            return $bike;
        };

        $bikes = Bicycle::query()->latest('created_at')->get()->map($addOverdueDetails);
        $bikeCategories = $bikes->groupBy('bike_type');
        $dashboardBikes = collect([
            'available' => Bicycle::query()
                ->where('status', 'available')
                ->latest('created_at')
                ->latest('bike_id')
                ->first(),
            'rented' => Bicycle::query()
                ->where('status', 'rented')
                ->latest('created_at')
                ->latest('bike_id')
                ->first(),
            'maintenance' => Bicycle::query()
                ->whereIn('status', ['repair', 'maintenance'])
                ->latest('created_at')
                ->latest('bike_id')
                ->first(),
        ])->filter();
        $dashboardBikes->each($addOverdueDetails);

        $staff = Staff::find(Session::get('staff_id'));
        $staffPermissions = $staff?->permissions ?? [];
        $issueReports = IssueReport::with('bike')
            ->where('staff_id', $staff?->staff_id)
            ->latest('reported_at')
            ->limit(10)
            ->get();
        $canAddInventory = in_array('Add Inventory', $staffPermissions, true);
        $canEditInventory = in_array('Manage Inventory', $staffPermissions, true)
            || in_array('Edit Inventory', $staffPermissions, true);
        $canDeleteInventory = in_array('Manage Inventory', $staffPermissions, true)
            || in_array('Delete Inventory', $staffPermissions, true);
        $nextBikeCode = Bicycle::nextQrCode('Mountain Bike');

        return view('staff.home', compact(
            'stats',
            'bikes',
            'bikeCategories',
            'dashboardBikes',
            'issueReports',
            'staffPermissions',
            'canAddInventory',
            'canEditInventory',
            'canDeleteInventory',
            'nextBikeCode'
        ));
    }

    public function exportBikeInventoryData(): JsonResponse
    {
        $bikes = Bicycle::orderBy('bike_id')->get([
            'bike_id',
            'qr_code',
            'model',
            'make',
            'bike_type',
            'status',
            'condition',
        ])->map(fn (Bicycle $bike): array => [
            'bike_id' => $bike->bike_id,
            'qr_code' => $bike->qr_code,
            'model' => $bike->model,
            'make' => $bike->make,
            'bike_type' => $bike->bike_type,
            'status' => ucfirst($bike->status),
            'condition' => ucfirst($bike->condition),
        ]);

        return response()->json([
            'generated_at' => now()->setTimezone('Asia/Manila')->format('F j, Y \a\t g:i A'),
            'bikes' => $bikes,
        ]);
    }

    public function todayActivityLogs(): JsonResponse
    {
        $logs = ActivityLog::query()
            ->whereDate('timestamp', today())
            ->orderByDesc('timestamp')
            ->orderByDesc('log_id')
            ->get();

        $adminNames = Admin::whereIn(
            'admin_id',
            $logs->where('user_type', 'admin')->pluck('user_id')->unique()
        )->pluck('full_name', 'admin_id');

        $staffMembers = Staff::whereIn(
            'staff_id',
            $logs->where('user_type', 'staff')->pluck('user_id')->unique()
        )->get(['staff_id', 'full_name', 'first_name', 'last_name'])->keyBy('staff_id');

        $exportLogs = $logs->map(function (ActivityLog $log) use ($adminNames, $staffMembers): array {
            $staff = $staffMembers->get($log->user_id);
            $actorName = match ($log->user_type) {
                'admin' => $adminNames->get($log->user_id, 'Unknown Admin'),
                'staff' => $staff
                    ? (trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) ?: $staff->full_name)
                    : 'Unknown Staff',
                default => 'System',
            };

            return [
                'log_id' => $log->log_id,
                'actor' => $actorName,
                'user_type' => ucfirst($log->user_type),
                'action' => ucwords(strtolower(str_replace('_', ' ', $log->action))),
                'details' => $log->details ?: '—',
                'timestamp' => $log->timestamp->format('M d, Y h:i A'),
            ];
        });

        return response()->json([
            'date' => today()->toDateString(),
            'logs' => $exportLogs,
        ]);
    }
}