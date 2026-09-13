<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bike;
use App\Models\Bicycle;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'available' => Bike::where('condition', 'Good')->where('status', 'Available')->count(),
            'rented'    => Bike::where('condition', 'Good')->where('status', 'Rented')->count(),
            'repair'    => Bike::where('condition', '!=', 'Good')->count(),
            'total'     => Bike::count(),
        ];
        $bikes = Bike::query()->latest()->get();

        return view('staff.home', compact('stats', 'bikes'));
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