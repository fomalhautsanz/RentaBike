<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bicycle;
use App\Models\IssueReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Staff issue reports (damage / missing / other).
 * Ma-save sa `issue_report` table nga "pending" para i-review sa Admin.
 */
class IssueReportController extends Controller
{
    public const TYPES = ['damage', 'missing', 'other'];

    // POST /staff/issue-reports
    public function store(Request $request): JsonResponse
    {
        // Pareha sa UI: ang Report screen makita ra sa staff nga naay "Handle Maintenance"
        abort_unless(
            in_array('Handle Maintenance', $request->attributes->get('staffPermissions', []), true),
            403,
            'You do not have access to maintenance reports.'
        );

        $validator = Validator::make($request->all(), [
            'issue_type'  => ['required', 'string', Rule::in(self::TYPES)],
            'bike_code'   => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
            'photo'       => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'bike_code.required'   => 'Please enter the Bike ID.',
            'description.required' => 'Please describe the issue.',
            'photo.mimes'          => 'The photo must be a JPG, PNG or WEBP image.',
            'photo.max'            => 'The photo must not be larger than 5 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();

        $bike = Bicycle::where('qr_code', trim($data['bike_code']))->first();
        if (! $bike) {
            return response()->json(['message' => 'Bike not found. Please check the Bike ID.'], 404);
        }

        $staffId   = (int) Session::get('staff_id');
        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('issue-reports', 'local');
            if (! $photoPath) {
                return response()->json(['message' => 'Could not save the photo. Please try again.'], 500);
            }
        }

        try {
            $report = DB::transaction(function () use ($bike, $staffId, $data, $photoPath) {
                $report = IssueReport::create([
                    'bike_id'     => $bike->bike_id,
                    'staff_id'    => $staffId,
                    'issue_type'  => $data['issue_type'],
                    'description' => trim($data['description']),
                    'photo_ref'   => $photoPath,
                    'status'      => 'pending',
                ]);

                DB::table('activity_log')->insert([
                    'user_id'   => $staffId,
                    'user_type' => 'staff',
                    'action'    => 'REPORT_ISSUE',
                    'details'   => 'Issue #' . $report->issue_id . ' (' . $data['issue_type'] . ') reported for ' . $bike->qr_code,
                ]);

                return $report;
            });
        } catch (\Throwable $e) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }
            report($e);

            return response()->json(['message' => 'Could not submit the report. Please try again.'], 500);
        }

        return response()->json([
            'message' => 'Report submitted successfully!',
            'report'  => [
                'issue_id'   => $report->issue_id,
                'bike_code'  => $bike->qr_code,
                'issue_type' => $report->issue_type,
                'status'     => $report->status,
            ],
        ], 201);
    }
}
