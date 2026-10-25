<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bicycle;

class DashboardController extends Controller
{
    public function index()
    {
        //sa rented, wala pay backend logic ang rent so this will show zero for now
        $stats = [
            'available' => Bicycle::where('status', 'available')->count(),
            'rented'    => Bicycle::where('status', 'rented')->count(),
            'repair'    => Bicycle::where('status', 'repair')->count(),
            'total'     => Bicycle::count(),
        ];

        return view('staff.home', compact('stats'));
    }
}