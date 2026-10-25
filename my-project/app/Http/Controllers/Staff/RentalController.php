<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bicycle;
use App\Models\Payment;
use App\Models\Rental;
use App\Support\RentalPricing;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

/**
 * Staff rentals human ma-scan ang QR:
 *  - store  : i-open ang rental (gamit ang stored procedure sp_open_rental)
 *  - extend : dugangan ang oras sa active rental
 *  - end    : i-close ang rental (gamit ang stored procedure sp_close_rental)
 *
 * Ang presyo gikan sa RentalPricing (server). Dili gyud mosalig sa gi-send sa client.
 */
class RentalController extends Controller
{
    // POST /staff/rentals
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code'          => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'borrower_name' => ['required', 'string', 'max:150'],
            'contact_no'    => ['required', 'string', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'minutes'       => ['required', 'integer'],
            'borrow_time'   => ['required', 'date_format:H:i'],
        ], [
            'contact_no.regex' => 'Please enter a valid contact number.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();

        $bike = Bicycle::where('qr_code', $data['code'])->first();
        if (! $bike) {
            return response()->json(['message' => 'Bike not found'], 404);
        }

        $status    = strtolower(trim((string) $bike->status));
        $condition = strtolower(trim((string) $bike->condition));

        if ($condition !== 'good' || in_array($status, ['maintenance', 'repair'], true)) {
            return response()->json(['message' => 'This bike cannot be rented'], 422);
        }
        if ($status !== 'available') {
            return response()->json(['message' => 'This bike is already rented.'], 409);
        }

        $minutes = (int) $data['minutes'];
        $price   = RentalPricing::priceFor($bike->bike_type, $minutes);
        if ($price === null) {
            return response()->json(['message' => 'Invalid rental option for this bike.'], 422);
        }

        $now   = RentalPricing::dbNow();
        $start = $this->resolveBorrowTime($data['borrow_time'], $now);
        if (! $start) {
            return response()->json(['message' => 'Borrow time must be within 30 minutes of the current time.'], 422);
        }

        $staffId = (int) Session::get('staff_id');

        try {
            // Ang procedure mo-create sa borrower, rental, payment ug i-set ang bike nga "rented"
            DB::statement('CALL sp_open_rental(?, ?, ?, ?, ?, ?, ?, @new_rental_id)', [
                trim($data['borrower_name']),
                trim($data['contact_no']),
                null, // ID photo reference (wala pa gi-save)
                $bike->bike_id,
                $staffId,
                $minutes / 60,
                $price, // gi-collect nga bayad
            ]);
            $rentalId = (int) DB::selectOne('SELECT @new_rental_id AS id')->id;
        } catch (QueryException $e) {
            return response()->json(['message' => $this->dbMessage($e, 'Could not open the rental.')], 422);
        }

        // fn_compute_rental_fee flat PHP 60/oras ra, mao nga i-correct nato ang fee
        // sumala sa bike type, ug i-set ang start_time sa gi-input nga borrow time.
        try {
            DB::table('rental')->where('rental_id', $rentalId)->update([
                'start_time' => $start->format('Y-m-d H:i:s'),
                'base_fee'   => $price,
                'total_fee'  => $price,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => "Rental #{$rentalId} was opened but its fee could not be set. Please check it.",
            ], 500);
        }

        $rental = Rental::with(['borrower', 'payment'])->find($rentalId);

        return response()->json([
            'message' => 'Bike assigned.',
            'bike'    => [
                'qr_code'   => $bike->qr_code,
                'name'      => $bike->name,
                'bike_type' => $bike->bike_type,
            ],
            'rental' => RentalPricing::summary($rental, $bike->bike_type, RentalPricing::dbNow()),
        ], 201);
    }

    // POST /staff/rentals/{rental}/extend
    public function extend(Request $request, Rental $rental): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'minutes' => ['required', 'integer'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $minutes = (int) $request->input('minutes');
        $staffId = (int) Session::get('staff_id');

        $result = DB::transaction(function () use ($rental, $minutes, $staffId) {
            // lockForUpdate para dili mag-doble kung duha ka staff ang mo-extend sa samang higayon
            $locked = Rental::lockForUpdate()->find($rental->getKey());

            if (! $locked || $locked->status !== 'active') {
                return ['error' => 'This rental is already finished.', 'status' => 409];
            }

            $bikeType = $locked->bike?->bike_type;
            $price    = RentalPricing::priceFor($bikeType, $minutes, true);
            if ($price === null) {
                return ['error' => 'Invalid extension option.', 'status' => 422];
            }

            $newBase = round((float) $locked->base_fee + $price, 2);
            $locked->update([
                'rental_duration_hrs' => round((float) $locked->rental_duration_hrs + $minutes / 60, 2),
                'base_fee'            => $newBase,
                'total_fee'           => round($newBase + (float) $locked->overtime_fee, 2),
            ]);

            // Ang gi-extend nga bayad gi-collect dayon
            $payment = Payment::where('rental_id', $locked->rental_id)->first();
            if ($payment) {
                $payment->update(['amount_paid' => round((float) $payment->amount_paid + $price, 2)]);
            } else {
                Payment::create([
                    'rental_id'      => $locked->rental_id,
                    'amount_paid'    => $price,
                    'payment_status' => 'pending',
                    'paid_at'        => RentalPricing::dbNow()->format('Y-m-d H:i:s'),
                ]);
            }

            DB::table('activity_log')->insert([
                'user_id'   => $staffId,
                'user_type' => 'staff',
                'action'    => 'EXTEND_RENTAL',
                'details'   => 'Rental #' . $locked->rental_id . ' extended by ' . $minutes . ' min | Added fee: ' . $price,
            ]);

            return ['rental_id' => $locked->rental_id, 'price' => $price];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['status']);
        }

        $updated = Rental::with(['borrower', 'payment', 'bike'])->find($result['rental_id']);

        return response()->json([
            'message' => 'Extended by ' . RentalPricing::durationLabel($minutes) . ' (+₱' . $this->money($result['price']) . ')',
            'rental'  => RentalPricing::summary($updated, $updated->bike?->bike_type, RentalPricing::dbNow()),
        ]);
    }

    // POST /staff/rentals/{rental}/end
    public function end(Rental $rental): JsonResponse
    {
        $rental->load(['bike', 'borrower', 'payment']);

        if ($rental->status !== 'active') {
            return response()->json(['message' => 'This rental is already finished.'], 409);
        }

        $summary = RentalPricing::summary($rental, $rental->bike?->bike_type, RentalPricing::dbNow());
        $staffId = (int) Session::get('staff_id');

        try {
            // Ang procedure mo-compute sa overtime, i-complete ang rental, ug i-balik ang bike nga "available".
            // Ang overtime fee gi-collect karon (p_extra_payment).
            DB::statement('CALL sp_close_rental(?, ?, ?)', [
                $rental->rental_id,
                $staffId,
                $summary['estimated_overtime_fee'],
            ]);
        } catch (QueryException $e) {
            return response()->json(['message' => $this->dbMessage($e, 'Could not end the session.')], 422);
        }

        $done  = Rental::with(['borrower', 'payment'])->find($rental->rental_id);
        $total = (float) $done->total_fee;
        $paid  = (float) ($done->payment?->amount_paid ?? 0);

        return response()->json([
            'message' => 'Session ended.',
            'receipt' => [
                'rental_id'      => $done->rental_id,
                'borrower_name'  => $done->borrower?->full_name,
                'time_used'      => RentalPricing::durationLabel((int) round(((float) $done->rental_duration_hrs) * 60)),
                'base_fee'       => (float) $done->base_fee,
                'overtime_fee'   => (float) $done->overtime_fee,
                'total_fee'      => $total,
                'amount_paid'    => $paid,
                'payment_status' => $done->payment?->payment_status,
                'balance_due'    => max(0.0, round($total - $paid, 2)),
            ],
        ]);
    }

    /**
     * Ang gi-input nga borrow time (HH:MM) mahimong 30 minutos gikan sa karon ra.
     * Gi-check ang gahapon/karon/ugma para dili ma-problema kung midnight.
     */
    private function resolveBorrowTime(string $hhmm, Carbon $now): ?Carbon
    {
        $best = null;
        foreach ([-1, 0, 1] as $offset) {
            $candidate = Carbon::parse($now->toDateString() . ' ' . $hhmm . ':00')->addDays($offset);
            if ($best === null
                || abs($candidate->getTimestamp() - $now->getTimestamp()) < abs($best->getTimestamp() - $now->getTimestamp())) {
                $best = $candidate;
            }
        }

        return abs($best->getTimestamp() - $now->getTimestamp()) <= 30 * 60 ? $best : null;
    }

    // Ang SIGNAL SQLSTATE '45000' sa stored procedure: ang message naa sa errorInfo[2]
    private function dbMessage(QueryException $e, string $fallback): string
    {
        $info = $e->errorInfo ?? [];
        if (($info[0] ?? null) === '45000' && ! empty($info[2])) {
            return (string) $info[2];
        }

        report($e);

        return $fallback;
    }

    private function money(float $amount): string
    {
        return floor($amount) == $amount ? (string) (int) $amount : number_format($amount, 2);
    }
}