<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bicycle;
use App\Models\Staff;
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
        $bikes = Bicycle::query()->orderByDesc('bike_id')->get();

        $staff = Staff::find(Session::get('staff_id'));
        $staffPermissions = $staff?->permissions ?? [];

        return view('staff.home', compact('stats', 'bikes', 'staffPermissions'));
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