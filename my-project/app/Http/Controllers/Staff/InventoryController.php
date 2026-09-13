<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bicycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'qr_code' => ['required', 'string', 'max:100', 'unique:bicycle,qr_code'],
            'model' => ['required', 'string', 'max:100'],
            'make' => ['required', 'string', 'max:100'],
            'bike_type' => ['required', 'string', 'max:50'],
            'condition' => ['required', 'in:good,repair,missing'],
        ]);

        Bicycle::create([
            ...$validated,
            'status' => $validated['condition'] === 'good' ? 'available' : 'repair',
        ]);

        return redirect()->route('staff.home')->with('success', 'Bike added successfully.');
    }
}