<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bicycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        return view('staff.home', [
            'bikes' => Bicycle::query()->orderByDesc('bike_id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:E-Scooter,Lady\'s/Men\'s Bike,Mountain Bike,City Bike,Kiddie Bikes'],
            'condition' => ['required', 'in:Good,Needs Repair,Missing'],
        ]);

        Bicycle::create([
            'qr_code' => $this->nextQrCode(),
            'model' => $validated['type'],
            'make' => 'RentaBike',
            'bike_type' => $validated['type'],
            'condition' => $this->databaseCondition($validated['condition']),
            'status' => $validated['condition'] === 'Good' ? 'available' : 'repair',
        ]);

        return redirect()->route('staff.home')->with('status', 'Bike added to inventory.');
    }

    public function update(Request $request, Bicycle $bike): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:E-Scooter,Lady\'s/Men\'s Bike,Mountain Bike,City Bike,Kiddie Bikes'],
            'condition' => ['required', 'in:Good,Needs Repair,Missing'],
        ]);

        $bike->update([
            'model' => $validated['type'],
            'bike_type' => $validated['type'],
            'condition' => $this->databaseCondition($validated['condition']),
            'status' => $validated['condition'] === 'Good'
                ? (strtolower($bike->status) === 'rented' ? 'rented' : 'available')
                : 'repair',
        ]);

        return redirect()->route('staff.home')->with('status', 'Bike updated in inventory.');
    }

    public function destroy(Bicycle $bike): RedirectResponse
    {
        $bike->delete();

        return redirect()->route('staff.home')->with('status', 'Bike removed from inventory.');
    }

    public function toggleStatus(Bicycle $bike): JsonResponse
    {
        if (strtolower($bike->condition) !== 'good') {
            return response()->json([
                'message' => 'This bike is marked Repair and cannot be rented.',
            ], 422);
        }

        $bike->update([
            'status' => strtolower($bike->status) === 'rented' ? 'available' : 'rented',
        ]);

        $status = ucfirst($bike->status);

        return response()->json([
            'status' => $status,
            'message' => $status === 'Rented' ? 'Bike marked as rented.' : 'Bike marked as available.',
        ]);
    }

    private function databaseCondition(string $condition): string
    {
        return match ($condition) {
            'Good' => 'good',
            'Missing' => 'missing',
            default => 'repair',
        };
    }

    private function nextQrCode(): string
    {
        $nextNumber = (int) Bicycle::query()->max('bike_id') + 1;

        return 'BK-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
