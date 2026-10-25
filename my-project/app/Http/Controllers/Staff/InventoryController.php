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

    public function nextBikeCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bike_type' => ['required', 'string', Rule::in(Bicycle::bikeTypes())],
            'exclude_qr_code' => ['nullable', 'string', 'exists:bicycle,qr_code'],
        ]);

        return response()->json([
            'bike_code' => Bicycle::nextQrCode(
                $validated['bike_type'],
                isset($validated['exclude_qr_code'])
                    ? (int) Bicycle::where('qr_code', $validated['exclude_qr_code'])->value('bike_id')
                    : null
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(
            in_array('Add Inventory', $request->attributes->get('staffPermissions', []), true),
            403
        );

        $validated = $request->validate([
            'model' => ['required', 'string', 'max:100'],
            'make' => ['required', 'string', 'max:100'],
            'bike_type' => ['required', 'string', Rule::in(Bicycle::bikeTypes())],
            'condition' => ['required', 'in:Good,Needs Repair,Missing'],
        ]);

        $bike = Bicycle::create([
            'qr_code' => Bicycle::nextQrCode($validated['bike_type']),
            'model' => $validated['model'],
            'make' => $validated['make'],
            'bike_type' => $validated['bike_type'],
            'condition' => strtolower($validated['condition']) === 'good' ? 'good' : (strtolower($validated['condition']) === 'missing' ? 'missing' : 'repair'),
            'status' => strtolower($validated['condition']) === 'good' ? 'available' : 'repair',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bike added to inventory.',
                'next_qr_code' => Bicycle::nextQrCode($bike->bike_type),
                'bike' => [
                    'id' => $bike->qr_code,
                    'qr_code' => $bike->qr_code,
                    'model' => $bike->model,
                    'make' => $bike->make,
                    'bike_type' => $bike->bike_type,
                    'condition' => $bike->condition,
                    'status' => $bike->status,
                ],
            ]);
        }

        return redirect()->route('staff.home', ['screen' => 'inventory'])
            ->with('success', 'Bike added to inventory.');
    }

    public function update(Request $request, Bicycle $bike): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'model' => ['required', 'string', 'max:100'],
            'make' => ['required', 'string', 'max:100'],
            'bike_type' => ['required', 'string', Rule::in(array_unique([
                ...Bicycle::bikeTypes(),
                $bike->bike_type,
            ]))],
            'condition' => ['required', 'in:Good,Needs Repair,Missing'],
        ]);

        $condition = strtolower($validated['condition']);
        $bike->update([
            'model' => $validated['model'],
            'make' => $validated['make'],
            'bike_type' => $validated['bike_type'],
            'qr_code' => $bike->bike_type === $validated['bike_type']
                ? $bike->qr_code
                : Bicycle::nextQrCode($validated['bike_type'], $bike->bike_id),
            'condition' => $condition === 'good' ? 'good' : ($condition === 'missing' ? 'missing' : 'repair'),
            'status' => $condition === 'good'
                ? ($bike->status === 'rented' ? 'rented' : 'available')
                : 'repair',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bike updated in inventory.',
                'bike' => [
                    'id' => $bike->qr_code,
                    'qr_code' => $bike->qr_code,
                    'model' => $bike->model,
                    'make' => $bike->make,
                    'bike_type' => $bike->bike_type,
                    'condition' => $bike->condition,
                    'status' => $bike->status,
                ],
            ]);
        }

        return redirect()->route('staff.home', ['screen' => 'inventory'])
            ->with('success', 'Bike updated in inventory.');
    }

    public function destroy(Request $request, Bicycle $bike): RedirectResponse|JsonResponse
    {
        if ($bike->status === 'rented') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'A rented bike cannot be removed from inventory.',
                ], 422);
            }

            return redirect()->route('staff.home', ['screen' => 'inventory'])
                ->with('success', 'A rented bike cannot be removed from inventory.');
        }

        $bike->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bike removed from inventory.',
                'deleted' => true,
            ]);
        }

        return redirect()->route('staff.home', ['screen' => 'inventory'])
            ->with('success', 'Bike removed from inventory.');
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
