<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bicycle;
use App\Models\Rental;
use App\Support\RentalPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Staff QR scanning: gi-tawag sa scanner screen human ma-basa ang QR (o ma-type
 * ang bike code). Mag-lookup ra siya sa bike; WALA siyay gina-usab sa database.
 * Ang tinuod nga pag-rent/return naa gihapon sa InventoryController::toggleStatus.
 */
class ScanController extends Controller
{
    public function show(string $code): JsonResponse
    {
        $code = trim($code);

        // Ayaw pagsalig sa client: ang QR code format ra ang gidawat (pananglitan RB-004).
        // Kung dili mo-match, wala gyud ni'y bike nga ingon ana.
        if ($code === '' || strlen($code) > 100 || ! preg_match('/^[A-Za-z0-9_-]+$/', $code)) {
            return response()->json(['message' => 'Bike not found'], 404);
        }

        $bike = Bicycle::where('qr_code', $code)->first();

        if (! $bike) {
            return response()->json(['message' => 'Bike not found'], 404);
        }

        $status = strtolower(trim((string) $bike->status));
        $condition = strtolower(trim((string) $bike->condition));

        $details = [
            'qr_code' => $bike->qr_code,
            'name' => $bike->name,
            'model' => $bike->model,
            'make' => $bike->make,
            'bike_type' => $bike->bike_type,
            'status' => $status,
            'condition' => $condition,
        ];

        // Repair/missing condition o maintenance/repair status = dili pwede i-rent.
        // Ang bisan unsa nga laing value (dili 'good' / dili available-rented) gi-block pud
        // para parehas sa InventoryController::toggleStatus.
        if ($condition !== 'good' || ! in_array($status, ['available', 'rented'], true)) {
            return response()->json([
                'message' => 'This bike cannot be rented',
                'bike' => $details,
            ], 422);
        }

        $now = RentalPricing::dbNow();

        // Rented: ipakita ang detalye sa active rental (extend / end session)
        if ($status === 'rented') {
            $rental = Rental::with(['borrower', 'payment'])
                ->where('bike_id', $bike->bike_id)
                ->where('status', 'active')
                ->latest('rental_id')
                ->first();

            return response()->json([
                'bike' => $details,
                'next_action' => 'return',
                'message' => 'Bike is currently rented.',
                // null kung wala'y rental record (daang data nga gi-mark ra nga rented)
                'rental' => $rental ? [
                    ...RentalPricing::summary($rental, $bike->bike_type, $now),
                    'borrower_id_url' => $rental->borrower?->id_photo_id
                        && preg_match('#\Aborrower-ids/[A-Za-z0-9._-]+\z#D', $rental->borrower->id_photo_id)
                        && Storage::disk('local')->exists($rental->borrower->id_photo_id)
                            ? route('staff.rentals.borrower-id', $rental->rental_id)
                            : null,
                ] : null,
            ]);
        }

        // Available: ipakita ang mga package/presyo para sa rental form
        return response()->json([
            'bike' => $details,
            'next_action' => 'rent',
            'message' => 'Bike is available.',
            'pricing' => [
                'rate_label' => RentalPricing::rateLabel($bike->bike_type),
                'options' => RentalPricing::describe(RentalPricing::startOptions($bike->bike_type)),
            ],
            // oras sa server (HH:MM) para sa default nga borrow time
            'server_time' => $now->format('H:i'),
        ]);
    }
}