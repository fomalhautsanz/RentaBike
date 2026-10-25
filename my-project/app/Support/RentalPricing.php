<?php

namespace App\Support;

use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Presyo sa rental matag klase sa bike. Ang SERVER ra ang mo-determine sa presyo,
 * dili mosalig sa client.
 *
 * NOTE: ang DB function fn_compute_rental_fee flat PHP 60/oras ra ug wala makahibalo
 * sa bike type, mao nga ang RentalController mo-update sa base_fee human ma-open
 * ang rental gamit sp_open_rental.
 */
class RentalPricing
{
    // E-Scooter: fixed packages (minutes => price)
    private const SCOOTER_PACKAGES = [15 => 50, 30 => 100, 60 => 200];

    // Uban nga bikes: presyo matag oras (type sa lowercase)
    private const HOURLY_RATES = [
        'city bike'         => 30,
        "lady's/men's bike" => 30,
        'mountain bike'     => 50,
        'tricycle'          => 100,
        'sidecar bike'      => 100, // "Sidecar Bike" sa admin = tricycle
        'road bike'         => 60,
        "children's bike"   => 20,
    ];
    private const DEFAULT_HOURLY_RATE = 30; // kung wala sa lista ang type

    private const HOURLY_START_MINUTES  = [60, 120, 180]; // 1, 2, 3 oras
    private const HOURLY_EXTEND_MINUTES = [30, 60];

    private static function normalize(?string $type): string
    {
        return strtolower(trim((string) $type));
    }

    public static function isScooter(?string $type): bool
    {
        return in_array(self::normalize($type), ['e-scooter', 'e scooter', 'escooter', 'scooter'], true);
    }

    public static function hourlyRate(?string $type): int
    {
        return self::isScooter($type)
            ? self::SCOOTER_PACKAGES[60]
            : (self::HOURLY_RATES[self::normalize($type)] ?? self::DEFAULT_HOURLY_RATE);
    }

    /** minutes => price para sa bag-ong rental */
    public static function startOptions(?string $type): array
    {
        return self::isScooter($type)
            ? self::SCOOTER_PACKAGES
            : self::hourlyOptions($type, self::HOURLY_START_MINUTES);
    }

    /** minutes => price para sa extend */
    public static function extendOptions(?string $type): array
    {
        return self::isScooter($type)
            ? self::SCOOTER_PACKAGES
            : self::hourlyOptions($type, self::HOURLY_EXTEND_MINUTES);
    }

    private static function hourlyOptions(?string $type, array $minutesList): array
    {
        $rate = self::hourlyRate($type);
        $options = [];
        foreach ($minutesList as $minutes) {
            $options[$minutes] = round($rate * $minutes / 60, 2);
        }

        return $options;
    }

    /** Presyo sa gipili nga minutes, o null kung dili valid para sa type. */
    public static function priceFor(?string $type, int $minutes, bool $extension = false): ?float
    {
        $options = $extension ? self::extendOptions($type) : self::startOptions($type);

        return isset($options[$minutes]) ? (float) $options[$minutes] : null;
    }

    public static function rateLabel(?string $type): string
    {
        if (self::isScooter($type)) {
            return '₱50 / 15 min · ₱100 / 30 min · ₱200 / 1 hour';
        }

        return '₱' . self::hourlyRate($type) . ' / hour';
    }

    /** Para sa JSON: list sa {minutes, label, price} */
    public static function describe(array $options): array
    {
        $list = [];
        foreach ($options as $minutes => $price) {
            $list[] = [
                'minutes' => (int) $minutes,
                'label'   => self::durationLabel((int) $minutes),
                'price'   => (float) $price,
            ];
        }

        return $list;
    }

    public static function durationLabel(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours} hr {$mins} min";
        }
        if ($hours > 0) {
            return $hours === 1 ? '1 hour' : "{$hours} hours";
        }

        return "{$mins} min";
    }

    /**
     * Charge each started overtime hour at the effective rate of the selected package.
     */
    public static function overtimePenalty(int $overtimeMinutes, float $hourlyRate): float
    {
        return $overtimeMinutes > 0
            ? round(ceil($overtimeMinutes / 60) * $hourlyRate, 2)
            : 0.0;
    }

    /**
     * Oras sa database (MySQL NOW()). Ang stored procedures mo-save sa start_time gamit
     * ang NOW(), ug ang app timezone UTC, mao nga dili ta mogamit og Carbon::now().
     */
    public static function dbNow(): Carbon
    {
        try {
            return Carbon::parse(DB::selectOne('SELECT NOW() AS now')->now);
        } catch (\Throwable $e) {
            return Carbon::now(); // fallback (pananglitan SQLite sa tests)
        }
    }

    /** Detalye sa active rental para sa staff screen. */
    public static function summary(Rental $rental, ?string $bikeType, Carbon $now): array
    {
        $start   = $rental->start_time;
        $minutes = (int) round(((float) $rental->rental_duration_hrs) * 60);
        $end     = $start->copy()->addMinutes($minutes);

        $secondsRemaining = $end->getTimestamp() - $now->getTimestamp();
        $overtimeMinutes  = $secondsRemaining < 0
            ? (int) floor(abs($secondsRemaining) / 60)
            : 0;

        $baseFee = (float) $rental->base_fee;
        $paid    = (float) ($rental->payment?->amount_paid ?? 0);
        $hourlyRate = $minutes > 0
            ? round($baseFee / ($minutes / 60), 2)
            : (float) self::hourlyRate($bikeType);
        $penalty = self::overtimePenalty($overtimeMinutes, $hourlyRate);

        $endLabel = $end->format('g:i A');
        if ($end->toDateString() !== $start->toDateString()) {
            $endLabel .= ' (next day)';
        }

        return [
            'rental_id'              => $rental->rental_id,
            'borrower_name'          => $rental->borrower?->full_name,
            'contact_no'             => $rental->borrower?->contact_no,
            'start_label'            => $start->format('g:i A'),
            'duration_minutes'       => $minutes,
            'duration_label'         => self::durationLabel($minutes),
            'expected_end_label'     => $endLabel,
            'seconds_remaining'      => $secondsRemaining,
            'overdue'                => $secondsRemaining < 0,
            'overtime_minutes'       => $overtimeMinutes,
            'overtime_rate_per_hour' => $hourlyRate,
            'base_fee'               => $baseFee,
            'amount_paid'            => $paid,
            'estimated_overtime_fee' => $penalty,
            'balance_due'            => max(0.0, round($baseFee + $penalty - $paid, 2)),
            'extend_options'         => self::describe(self::extendOptions($bikeType)),
        ];
    }
}