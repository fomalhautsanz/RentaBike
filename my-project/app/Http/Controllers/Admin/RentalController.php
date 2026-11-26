<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Bicycle;
use App\Models\Borrower;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Staff;
use App\Support\ActivityLogger;
use App\Support\AdminAccess;
use App\Support\RentalPricing;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RentalController extends Controller
{
    public function options(Bicycle $bike): JsonResponse
    {
        abort_unless(AdminAccess::allows('Edit Inventory'), 403);

        if ($bike->condition !== 'good' || $bike->status !== 'available') {
            return response()->json(['message' => 'This bike is not available for rental.'], 409);
        }

        return response()->json([
            'bike' => [
                'qr_code' => $bike->qr_code,
                'name' => $bike->name,
                'bike_type' => $bike->bike_type,
            ],
            'pricing' => [
                'options' => RentalPricing::describe(RentalPricing::startOptions($bike->bike_type)),
            ],
            'server_time' => RentalPricing::dbNow()->format('H:i'),
        ]);
    }

    public function store(Request $request, Bicycle $bike): JsonResponse
    {
        abort_unless(AdminAccess::allows('Edit Inventory'), 403);

        $data = $request->validate([
            'borrower_name' => ['required', 'string', 'max:150'],
            'contact_no' => ['required', 'string', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'borrower_id' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'minutes' => ['required', 'integer'],
            'borrow_time' => ['required', 'date_format:H:i'],
        ], [
            'contact_no.regex' => 'Please enter a valid contact number.',
            'borrower_id.uploaded' => 'The borrower ID must be 2 MB or smaller. Please choose a smaller file.',
            'borrower_id.max' => 'The borrower ID must be 2 MB or smaller.',
        ]);

        $actor = $request->attributes->get('authenticatedStaff') ?? $request->user();
        if ($actor instanceof Admin) {
            $actorFields = ['staff_id' => null, 'admin_id' => $actor->admin_id];
        } elseif ($actor instanceof Staff) {
            $actorFields = ['staff_id' => $actor->staff_id, 'admin_id' => null];
        } else {
            abort(403);
        }

        $minutes = (int) $data['minutes'];
        if (RentalPricing::priceFor($bike->bike_type, $minutes) === null) {
            return response()->json(['message' => 'Invalid rental option for this bike.'], 422);
        }

        $start = RentalPricing::resolveBorrowTime($data['borrow_time'], RentalPricing::dbNow());
        if (! $start) {
            return response()->json(['message' => 'Borrow time must be within 30 minutes of the current time.'], 422);
        }

        $idPhotoPath = $request->file('borrower_id')->store('borrower-ids', 'local');
        if (! $idPhotoPath) {
            return response()->json(['message' => 'Could not save the borrower ID. Please try again.'], 500);
        }

        try {
            $result = DB::transaction(function () use ($bike, $data, $minutes, $start, $idPhotoPath, $actorFields, $request) {
                $lockedBike = Bicycle::whereKey($bike->getKey())->lockForUpdate()->firstOrFail();

                if ($lockedBike->condition !== 'good') {
                    return ['error' => 'This bike cannot be rented because it is not in good condition.', 'status' => 422];
                }

                if ($lockedBike->status !== 'available') {
                    return ['error' => 'This bike is already rented or unavailable.', 'status' => 409];
                }

                $price = RentalPricing::priceFor($lockedBike->bike_type, $minutes);
                if ($price === null) {
                    return ['error' => 'Invalid rental option for this bike.', 'status' => 422];
                }

                $now = RentalPricing::dbNow();
                $start = RentalPricing::resolveBorrowTime($data['borrow_time'], $now);
                if (! $start) {
                    return ['error' => 'Borrow time must be within 30 minutes of the current time.', 'status' => 422];
                }

                $borrower = Borrower::create([
                    'full_name' => trim($data['borrower_name']),
                    'contact_no' => trim($data['contact_no']),
                    'id_photo_id' => $idPhotoPath,
                ]);

                $rental = Rental::create([
                    'borrower_id' => $borrower->borrower_id,
                    'bike_id' => $lockedBike->bike_id,
                    ...$actorFields,
                    'start_time' => $start->format('Y-m-d H:i:s'),
                    'rental_duration_hrs' => $minutes / 60,
                    'base_fee' => $price,
                    'overtime_fee' => 0,
                    'total_fee' => $price,
                    'status' => 'active',
                ]);

                Payment::create([
                    'rental_id' => $rental->rental_id,
                    'amount_paid' => $price,
                    'payment_status' => 'pending',
                    'paid_at' => $now->format('Y-m-d H:i:s'),
                ]);

                $lockedBike->update(['status' => 'rented']);

                ActivityLogger::record(
                    $request,
                    'OPEN_RENTAL',
                    'Rental #' . $rental->rental_id
                        . ' opened for borrower: ' . trim($data['borrower_name'])
                        . ' | Bike ID: ' . $lockedBike->qr_code
                        . ' | Duration: ' . ($minutes / 60)
                        . ' hrs | Base Fee: ' . $price
                );

                return ['rental_id' => $rental->rental_id];
            });
        } catch (QueryException $e) {
            Storage::disk('local')->delete($idPhotoPath);
            report($e);

            return response()->json(['message' => 'Could not open the rental. Please try again.'], 500);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($idPhotoPath);
            throw $e;
        }

        if (isset($result['error'])) {
            Storage::disk('local')->delete($idPhotoPath);

            return response()->json(['message' => $result['error']], $result['status']);
        }

        $rental = Rental::with(['borrower', 'payment'])->findOrFail($result['rental_id']);

        return response()->json([
            'message' => 'Bike assigned.',
            'bike' => [
                'qr_code' => $bike->qr_code,
                'name' => $bike->name,
                'bike_type' => $bike->bike_type,
            ],
            'rental' => RentalPricing::summary($rental, $bike->bike_type, RentalPricing::dbNow()),
        ], 201);
    }
}
