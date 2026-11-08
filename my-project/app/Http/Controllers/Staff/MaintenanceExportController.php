<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\IssueReport;
use App\Support\MaintenanceLogs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceExportController extends Controller
{
    /**
     * Staff maintenance logs export. JSON ra ang gi-return; ang ExcelJS sa
     * Staff Home ang mohimo sa file (parehas sa Activity Logs ug Inventory
     * export). Staff nga naay "Handle Maintenance" ra ang pwede, gi-check
     * sa backend, dili lang gi-hide ang button.
     */
    public function export(Request $request): JsonResponse
    {
        $permissions = $request->attributes->get('staffPermissions', []);
        abort_unless(in_array('Handle Maintenance', $permissions, true), 403);

        $logs = MaintenanceLogs::query()->get()
            ->map(fn (IssueReport $report): array => (array) MaintenanceLogs::present($report))
            ->values();

        return response()->json([
            'date' => now()->setTimezone('Asia/Manila')->toDateString(),
            'generated_at' => now()->setTimezone('Asia/Manila')->format('F j, Y \a\t g:i A'),
            'logs' => $logs,
        ]);
    }
}
