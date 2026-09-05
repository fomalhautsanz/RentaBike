<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bicycle;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'available' => Bicycle::where('status', 'available')->count(),
            'rented'    => Bicycle::where('status', 'rented')->count(),
            'repair'    => Bicycle::where('status', 'repair')->count(),
            'total'     => Bicycle::count(),
        ];
        $bikes = Bicycle::orderByDesc('bike_id')->limit(10)->get();

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