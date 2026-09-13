<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bike;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        return view('staff.home', [
            'bikes' => Bike::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:E-Scooter,Lady\'s/Men\'s Bike,Mountain Bike,City Bike,Kiddie Bikes'],
            'condition' => ['required', 'in:Good,Needs Repair,Missing'],
        ]);

        Bike::create([
            ...$validated,
            'name' => $validated['type'],
            'status' => $validated['condition'] === 'Good'
                ? 'Available'
                : 'Maintenance',
        ]);

        return redirect()->route('staff.home')->with('status', 'Bike added to inventory.');
    }

    public function toggleStatus(Bike $bike): JsonResponse
    {
        if ($bike->condition !== 'Good') {
            return response()->json([
                'message' => 'This bike is marked Repair and cannot be rented.',
            ], 422);
        }

        $bike->update([
            'status' => $bike->status === 'Rented' ? 'Available' : 'Rented',
        ]);

        return response()->json([
            'status' => $bike->status,
            'message' => $bike->status === 'Rented' ? 'Bike marked as rented.' : 'Bike marked as available.',
        ]);
    }
}
