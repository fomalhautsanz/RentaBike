<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IssueReport;
use App\Support\MaintenanceLogs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    /**
     * I-save ang status sa issue report gikan sa "Update Report" modal.
     * Kung "resolved", butangan og resolved_at; kung i-balik, i-clear.
     * Kung resolved na ug wala nay laing open report sa bike, i-balik ang
     * bike sa "available" (dili hilabtan kung rented).
     */
    public function updateStatus(Request $request, IssueReport $issueReport): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(MaintenanceLogs::STATUSES))],
        ]);

        $bikeReleased = DB::transaction(function () use ($issueReport, $validated) {
            $issueReport->status = $validated['status'];
            $issueReport->resolved_at = $validated['status'] === 'resolved'
                ? ($issueReport->resolved_at ?? now())
                : null;
            $issueReport->save();

            $bike = $issueReport->bike;
            if ($validated['status'] !== 'resolved' || !$bike) {
                return false;
            }

            // naa pay laing pending / in progress nga report sa parehas nga bike? ayaw sa i-release
            $hasOpenReports = IssueReport::where('bike_id', $bike->bike_id)
                ->where('issue_id', '!=', $issueReport->issue_id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->exists();

            if ($hasOpenReports || $bike->status === 'rented') {
                return false;
            }

            $bike->status = 'available';
            $bike->condition = 'good';
            $bike->save();

            return true;
        });

        return response()->json([
            'message'       => 'Report updated.',
            'bike_released' => $bikeReleased,
            'report'        => MaintenanceLogs::present($issueReport->load(['bike', 'staff'])),
        ]);
    }
}
