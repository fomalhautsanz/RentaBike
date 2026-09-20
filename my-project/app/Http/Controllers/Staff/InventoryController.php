<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bicycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('staff.home');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'qr_code' => ['required', 'string', 'max:100', 'unique:bicycle,qr_code'],
            'model' => ['required', 'string', 'max:100'],
            'make' => ['required', 'string', 'max:100'],
            'bike_type' => ['required', 'string', 'max:50'],
            'condition' => ['required', 'in:Good,Needs Repair,Missing'],
        ]);

        Bicycle::create([
            'qr_code' => $validated['qr_code'],
            'model' => $validated['model'],
            'make' => $validated['make'],
            'bike_type' => $validated['bike_type'],
            'condition' => strtolower($validated['condition']) === 'good' ? 'good' : (strtolower($validated['condition']) === 'missing' ? 'missing' : 'repair'),
            'status' => strtolower($validated['condition']) === 'good' ? 'available' : 'repair',
        ]);

        return redirect()->route('staff.home')->with('status', 'Bike added to inventory.');
    }

    public function update(Request $request, Bicycle $bike): RedirectResponse
    {
        $validated = $request->validate([
            'qr_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('bicycle', 'qr_code')->ignore($bike->bike_id, 'bike_id'),
            ],
            'model' => ['required', 'string', 'max:100'],
            'make' => ['required', 'string', 'max:100'],
            'bike_type' => ['required', 'string', 'max:50'],
            'condition' => ['required', 'in:Good,Needs Repair,Missing'],
        ]);

        $condition = strtolower($validated['condition']);
        $bike->update([
            'qr_code' => $validated['qr_code'],
            'model' => $validated['model'],
            'make' => $validated['make'],
            'bike_type' => $validated['bike_type'],
            'condition' => $condition === 'good' ? 'good' : ($condition === 'missing' ? 'missing' : 'repair'),
            'status' => $condition === 'good'
                ? ($bike->status === 'rented' ? 'rented' : 'available')
                : 'repair',
        ]);

        return redirect()->route('staff.home')->with('status', 'Bike updated in inventory.');
    }

    public function destroy(Bicycle $bike): RedirectResponse
    {
        if ($bike->status === 'rented') {
            return redirect()->route('staff.home')
                ->with('status', 'A rented bike cannot be removed from inventory.');
        }

        $bike->delete();

        return redirect()->route('staff.home')->with('status', 'Bike removed from inventory.');
    }

    public function toggleStatus(Bicycle $bike): JsonResponse
    {
        if ($bike->condition !== 'good') {
            return response()->json([
                'message' => 'This bike is marked Repair and cannot be rented.',
            ], 422);
        }

        $bike->update([
            'status' => $bike->status === 'rented' ? 'available' : 'rented',
        ]);

        return response()->json([
            'status' => $bike->status,
            'message' => $bike->status === 'rented' ? 'Bike marked as rented.' : 'Bike marked as available.',
        ]);
    }
}
