<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bicycle;
use App\Models\Rental;
use App\Models\Staff;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        ActivityLogger::record(
            $request,
            'ADD_BIKE',
            'Added bike: ' . $bike->model . ' ' . $bike->make
                . ' (' . $bike->qr_code . ')'
                . ' | Bike type: ' . $bike->bike_type
                . ' | Condition: ' . $bike->condition
                . ' | Status: ' . $bike->status
        );

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
        // Security: dili lang i-hide ang button, i-check pud sa server
        abort_unless(
            in_array('Edit Inventory', $request->attributes->get('staffPermissions', []), true),
            403,
            'You do not have the "Edit Inventory" privilege.'
        );

        if (!$this->hasBikeConfirmation($request, $bike, 'edit')) {
            return $this->bikeConfirmationRequired($request, 'Confirm your password before editing this bike.');
        }

        $validated = $request->validate([
            'model' => ['required', 'string', 'max:100'],
            'make' => ['required', 'string', 'max:100'],
            'bike_type' => ['required', 'string', Rule::in(array_unique([
                ...Bicycle::bikeTypes(),
                $bike->bike_type,
            ]))],
            'condition' => ['required', Rule::in([
                'Good', 'Damage', 'Needs Repair', 'Missing', 'Other',
                'good', 'repair', 'missing', 'other',
            ])],
        ]);

        $before = [
            'Model' => $bike->model,
            'Make' => $bike->make,
            'Bike type' => $bike->bike_type,
            'Bike code' => $bike->qr_code,
            'Status' => $bike->status,
            'Condition' => $bike->condition,
        ];

        $condition = strtolower($validated['condition']);
        $condition = in_array($condition, ['good', 'missing', 'other'], true)
            ? $condition
            : 'repair';
        $bike->update([
            'model' => $validated['model'],
            'make' => $validated['make'],
            'bike_type' => $validated['bike_type'],
            'qr_code' => $bike->bike_type === $validated['bike_type']
                ? $bike->qr_code
                : Bicycle::nextQrCode($validated['bike_type'], $bike->bike_id),
            'condition' => $condition,
            'status' => $condition === 'good'
                ? ($bike->status === 'rented' ? 'rented' : 'available')
                : 'repair',
        ]);

        $after = [
            'Model' => $bike->model,
            'Make' => $bike->make,
            'Bike type' => $bike->bike_type,
            'Bike code' => $bike->qr_code,
            'Status' => $bike->status,
            'Condition' => $bike->condition,
        ];
        $changes = [];
        foreach ($before as $label => $oldValue) {
            if ($oldValue !== $after[$label]) {
                $changes[$label] = ['before' => $oldValue, 'after' => $after[$label]];
            }
        }

        ActivityLogger::record(
            $request,
            'UPDATE_BIKE',
            'Updated bike: ' . $bike->model . ' ' . $bike->make
                . ' (' . $bike->qr_code . ')'
                . ' | Bike ID: ' . $bike->qr_code
                . ' | Database ID: ' . $bike->bike_id
                . ' | Changes: ' . ActivityLogger::formatChanges($changes)
        );
        $this->clearBikeConfirmation($request, $bike, 'edit');

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
        abort_unless(
            in_array('Delete Inventory', $request->attributes->get('staffPermissions', []), true),
            403,
            'You do not have the "Delete Inventory" privilege.'
        );

        if (!$this->hasBikeConfirmation($request, $bike, 'delete')) {
            return $this->bikeConfirmationRequired($request, 'Confirm your password before deleting this bike.');
        }

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
        $this->clearBikeConfirmation($request, $bike, 'delete');

        ActivityLogger::record(
            $request,
            'DELETE_BIKE',
            'Deleted bike: ' . $bike->model . ' ' . $bike->make
                . ' (' . $bike->qr_code . ')'
                . ' | Bike ID: ' . $bike->qr_code
                . ' | Database ID: ' . $bike->bike_id
                . ' | Bike type: ' . $bike->bike_type
                . ' | Condition: ' . $bike->condition
                . ' | Status: ' . $bike->status
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bike removed from inventory.',
                'deleted' => true,
            ]);
        }

        return redirect()->route('staff.home', ['screen' => 'inventory'])
            ->with('success', 'Bike removed from inventory.');
    }

    public function verifyBikeAction(Request $request, Bicycle $bike): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['edit', 'delete'])],
            'password' => ['required', 'string'],
        ]);
        $privilege = $validated['action'] === 'edit' ? 'Edit Inventory' : 'Delete Inventory';

        abort_unless(
            in_array($privilege, $request->attributes->get('staffPermissions', []), true),
            403,
            "You do not have the \"{$privilege}\" privilege."
        );

        $staff = $request->attributes->get('authenticatedStaff');
        abort_unless($staff instanceof Staff, 401);

        if (!Hash::check($validated['password'], $staff->password_hash)) {
            return response()->json(['message' => 'The password is incorrect.'], 422);
        }

        $request->session()->put(
            $this->bikeConfirmationKey((int) $staff->staff_id, $bike, $validated['action']),
            now()->addMinutes(5)->timestamp
        );

        return response()->json(['message' => 'Password confirmed.']);
    }

    private function bikeConfirmationKey(int $staffId, Bicycle $bike, string $action): string
    {
        return "staff_bike_action_confirmations.{$staffId}.{$bike->getKey()}.{$action}";
    }

    private function hasBikeConfirmation(Request $request, Bicycle $bike, string $action): bool
    {
        $staffId = (int) $request->session()->get('staff_id');
        if ($staffId < 1) {
            return false;
        }

        $expiresAt = $request->session()->get($this->bikeConfirmationKey($staffId, $bike, $action));

        return is_numeric($expiresAt) && (int) $expiresAt >= now()->timestamp;
    }

    private function clearBikeConfirmation(Request $request, Bicycle $bike, string $action): void
    {
        $staffId = (int) $request->session()->get('staff_id');
        if ($staffId > 0) {
            $request->session()->forget($this->bikeConfirmationKey($staffId, $bike, $action));
        }
    }

    private function bikeConfirmationRequired(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return redirect()->route('staff.home', ['screen' => 'inventory'])
            ->withErrors(['bike_confirmation' => $message]);
    }

    /**
     * Fallback "return" para sa rented bike nga walay active rental record.
     * Security: rented → available ra; ang pag-rent kinahanglan moagi sa rental flow
     * (para naay borrower, ID ug payment record).
     */
    public function toggleStatus(Request $request, Bicycle $bike): JsonResponse
    {
        if ($bike->status !== 'rented') {
            return response()->json([
                'message' => 'Use the rental form to rent out a bike.',
            ], 422);
        }

        if (Rental::where('bike_id', $bike->bike_id)->where('status', 'active')->exists()) {
            return response()->json([
                'message' => 'This bike has an active rental. End the rental session instead.',
            ], 422);
        }

        $bike->update(['status' => 'available']);
        ActivityLogger::record(
            $request,
            'UPDATE_BIKE_STATUS',
            'Bike: ' . $bike->model . ' ' . $bike->make
                . ' (' . $bike->qr_code . ')'
                . ' | Status: rented -> available'
        );

        return response()->json([
            'status' => $bike->status,
            'message' => 'Bike marked as available.',
        ]);
    }
}
