<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bicycle;
use App\Models\Rental;
use App\Support\RentalPricing;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // Placeholder: i-usab ni sa tinuod nga oras sa shop kada adlaw (para sa Utilization).
    private const OPEN_HOURS_PER_DAY = 14;

    public function data(Request $request): JsonResponse
    {
        $range = $request->validate(['range' => ['required', 'in:ytd,30,90,12m']])['range'];

        // Ang start_time gi-save gamit ang MySQL NOW(), ug UTC ang app, so dbNow() ang gamiton.
        $end = RentalPricing::dbNow();
        $start = match ($range) {
            'ytd' => $end->copy()->startOfYear(),
            '30'  => $end->copy()->subDays(29)->startOfDay(),
            '90'  => $end->copy()->subDays(89)->startOfDay(),
            '12m' => $end->copy()->startOfMonth()->subMonths(11),
        };

        // Ang period nga i-compare para sa percentage changes.
        [$prevStart, $prevEnd] = $range === 'ytd'
            ? [$start->copy()->subYear(), $end->copy()->subYear()]
            : [$start->copy()->subSeconds((int) $start->diffInSeconds($end)), $start->copy()];

        $current = $this->summary($start, $end);
        $previous = $this->summary($prevStart, $prevEnd);

        return response()->json([
            'stats' => [
                'revenue'   => $this->stat((float) $current->revenue, (float) $previous->revenue),
                'rentals'   => $this->stat((int) $current->rentals, (int) $previous->rentals),
                'customers' => $this->stat((int) $current->customers, (int) $previous->customers),
                'duration'  => $this->stat(round((float) $current->avg_duration, 1), round((float) $previous->avg_duration, 1)),
            ],
            'trend'     => $this->trend($range, $start, $end),
            'peak'      => $this->peakHours($start, $end),
            'bikeTypes' => $this->bikeTypes($start, $end),
        ]);
    }

    // Tanan query diri mosugod, para pareho ang rentals nga gi-ihap sa tanan numbers.
    private function completed($start, $end)
    {
        return Rental::where('rental.status', 'completed')
            ->whereBetween('rental.start_time', [$start, $end]);
    }

    private function summary($start, $end)
    {
        return $this->completed($start, $end)
            ->selectRaw('COALESCE(SUM(total_fee), 0) as revenue, COUNT(*) as rentals,
                COUNT(DISTINCT borrower_id) as customers,
                COALESCE(AVG(rental_duration_hrs), 0) as avg_duration')
            ->first();
    }

    // change = null kung walay ika-compare, para dili mag-divide by zero.
    private function stat(int|float $current, int|float $previous): array
    {
        return [
            'value'  => $current,
            'change' => $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : null,
        ];
    }

    private function trend(string $range, $start, $end): array
    {
        $step = ['ytd' => '1 month', '12m' => '1 month', '30' => '1 day', '90' => '1 week'][$range];

        $rows = $this->completed($start, $end)
            ->selectRaw('DATE(start_time) as day, SUM(total_fee) as revenue, COUNT(*) as total')
            ->groupBy('day')
            ->get();

        $trend = ['labels' => [], 'revenue' => [], 'rentals' => []];

        foreach (CarbonPeriod::create($start, $step, $end) as $bucket) {
            $from = $bucket->toDateString();
            $to = $bucket->copy()->add($step)->toDateString();
            $inBucket = $rows->filter(fn ($row) => $row->day >= $from && $row->day < $to);

            $trend['labels'][] = $bucket->format($step === '1 month' ? 'M Y' : 'M j');
            $trend['revenue'][] = (float) $inBucket->sum('revenue');
            $trend['rentals'][] = (int) $inBucket->sum('total');
        }

        return $trend;
    }

    private function peakHours($start, $end): array
    {
        $counts = $this->completed($start, $end)
            ->selectRaw('FLOOR(HOUR(start_time) / 2) * 2 as hr, COUNT(*) as total')
            ->groupBy('hr')
            ->pluck('total', 'hr');

        $labels = [];
        $data = [];

        foreach ([6, 8, 10, 12, 14, 16, 18, 20] as $hour) {
            $labels[] = ($hour > 12 ? $hour - 12 : $hour) . ($hour < 12 ? 'AM' : 'PM');
            $data[] = (int) ($counts[$hour] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function bikeTypes($start, $end): array
    {
        $days = max(1, (int) ceil($start->diffInDays($end)));

        $fleet = Bicycle::selectRaw('bike_type, COUNT(*) as count')
            ->groupBy('bike_type')
            ->orderBy('bike_type')
            ->pluck('count', 'bike_type');

        $stats = $this->completed($start, $end)
            ->join('bicycle', 'bicycle.bike_id', '=', 'rental.bike_id')
            ->selectRaw('bicycle.bike_type, COUNT(*) as rentals, SUM(total_fee) as revenue,
                AVG(rental_duration_hrs) as avg_duration, SUM(rental_duration_hrs) as hours')
            ->groupBy('bicycle.bike_type')
            ->get()
            ->keyBy('bike_type');

        // Sugod sa fleet para makita gihapon ang types nga walay rentals.
        return $fleet->map(function ($count, $type) use ($stats, $days) {
            $row = $stats->get($type);
            $openHours = $count * self::OPEN_HOURS_PER_DAY * $days;

            return [
                'type'         => $type,
                'rentals'      => (int) ($row->rentals ?? 0),
                'revenue'      => (float) ($row->revenue ?? 0),
                'avg_duration' => round((float) ($row->avg_duration ?? 0), 1),
                'utilization'  => (int) min(100, round(((float) ($row->hours ?? 0) / $openHours) * 100)),
            ];
        })->values()->all();
    }
}
