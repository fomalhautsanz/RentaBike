<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bicycle;
use App\Models\Rental;
use App\Models\Staff;
use App\Support\RentalPricing;
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
            'staffPermissions',
            'canAddInventory',
            'canEditInventory',
            'canDeleteInventory',
            'nextBikeCode'
        ));
    }

    public function exportStaffDashboardCsv()
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
        }, 'staff-dashboard.csv', ['Content-Type' => 'text/csv']);
    }
}