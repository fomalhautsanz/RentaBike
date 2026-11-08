<?php

namespace App\Support;

use App\Models\IssueReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Maintenance logs: kani ang source sa real data gikan sa `issue_report`
 * table (gi-file sa staff). Gi-butang nako dinhi ang query, filters ug
 * pag-format para dili mugahi ang DashboardController, para parehas ang
 * data sa Admin page ug sa Staff export, ug para sayon i-test.
 */
class MaintenanceLogs
{
    // db value => gipakita sa UI
    public const STATUSES = [
        'pending'     => 'Pending',
        'in_progress' => 'In Progress',
        'resolved'    => 'Resolved',
    ];

    public const TYPES = [
        'damage'  => 'Damage',
        'missing' => 'Missing Bike',
        'other'   => 'Other',
    ];

    public static function reportCode(int $issueId): string
    {
        return sprintf('RPT-%04d', $issueId);
    }

    /**
     * Tanang issue_report rows, pinakabag-o una.
     */
    public static function query(): Builder
    {
        return IssueReport::query()
            ->with(['bike', 'staff'])
            ->orderByDesc('reported_at')
            ->orderByDesc('issue_id');
    }

    /**
     * I-validate ang maintenance filters sa Admin page. Naka-prefix og
     * `maint_` para dili mubangga sa Activity Logs filters (`log_*`).
     */
    public static function validateFilters(Request $request): array
    {
        return $request->validate([
            'maint_search' => ['nullable', 'string', 'max:100'],
            'maint_status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'maint_type'   => ['nullable', Rule::in(array_keys(self::TYPES))],
            'maint_period' => ['nullable', Rule::in(['all', '24', '168', '720', 'custom'])],
            'maint_from'   => ['nullable', 'required_if:maint_period,custom', 'date'],
            'maint_to'     => ['nullable', 'required_if:maint_period,custom', 'date', 'after_or_equal:maint_from'],
        ]);
    }

    /**
     * I-apply ang filters (search, status, type, date) sa query.
     */
    public static function filtered(array $filters): Builder
    {
        $query = self::query();

        if (!empty($filters['maint_status'])) {
            $query->where('status', $filters['maint_status']);
        }

        if (!empty($filters['maint_type'])) {
            $query->where('issue_type', $filters['maint_type']);
        }

        // parehas sa Activity Logs: last 24h / 7 days / 30 days o custom range
        $period = $filters['maint_period'] ?? 'all';
        if (in_array($period, ['24', '168', '720'], true)) {
            $query->where('reported_at', '>=', now()->subHours((int) $period));
        } elseif ($period === 'custom') {
            $query->whereDate('reported_at', '>=', $filters['maint_from'])
                ->whereDate('reported_at', '<=', $filters['maint_to']);
        }

        $search = trim((string) ($filters['maint_search'] ?? ''));
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';

            $query->where(function (Builder $q) use ($search, $like) {
                // "RPT-0012" o "12" => pangitaon pud sa issue_id
                if (preg_match('/^(?:RPT-?)?0*(\d+)$/i', $search, $m)) {
                    $q->orWhere('issue_id', (int) $m[1]);
                }

                $q->orWhere('description', 'like', $like)
                    ->orWhereHas('bike', fn (Builder $b) => $b
                        ->where('qr_code', 'like', $like)
                        ->orWhere('model', 'like', $like)
                        ->orWhere('make', 'like', $like))
                    ->orWhereHas('staff', fn (Builder $s) => $s
                        ->where('full_name', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like));
            });
        }

        return $query;
    }

    /**
     * I-map ang issue_report row ngadto sa shape nga gigamit sa maintenance page.
     */
    public static function present(IssueReport $report): object
    {
        $staff = $report->staff;
        $reporter = $staff
            ? (trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) ?: $staff->full_name)
            : 'Unknown';

        $status = self::STATUSES[$report->status] ?? ucwords(str_replace('_', ' ', (string) $report->status));
        $type = self::TYPES[$report->issue_type] ?? ucfirst((string) $report->issue_type);

        return (object) [
            'report_code' => self::reportCode((int) $report->issue_id),
            'type'        => $type,
            'bike_name'   => $report->bike?->name ?? 'N/A',
            'bike_code'   => $report->bike?->bike_code ?? '',
            'reporter'    => $reporter,
            'description' => (string) $report->description,
            // Activity Logs format ang gigamit: walay timezone conversion
            'date'        => $report->reported_at?->format('M d, Y h:i A') ?? '—',
            'resolved_at' => $report->resolved_at?->format('M d, Y h:i A') ?? '',
            'status'      => $status,
        ];
    }
}
