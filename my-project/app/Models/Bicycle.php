<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Mag-gamit ta og model kay kinahanglan gyud siya para makakuha ta
 * og data gikan sa database gamit ang klaro nga PHP command imbes
 * literal SQL. Mas sayon ra pud siya basahon, tapos mao ni ang
 * gitawag sa controller (pananglitan `DashboardController`) para
 * makakuha og real data gikan sa mga table, gets?
 */

class Bicycle extends Model
{
    private const BIKE_TYPE_PREFIXES = [
        'mountain bike' => 'MB',
        'city bike' => 'CB',
        "lady's/men's bike" => 'LM',
        'e-scooter' => 'ES',
        'road bike' => 'RB',
        'sidecar bike' => 'SB',
        "children's bike" => 'CH',
        'standard' => 'ST',
    ];

    protected $table = 'bicycle';
    protected $primaryKey = 'bike_id';
    public $timestamps = false;

    public function getRouteKeyName(): string
    {
        return 'qr_code';
    }

    public function getBikeCodeAttribute(): string
    {
        return $this->qr_code;
    }

    public function getNameAttribute(): string
    {
        return trim($this->model . ' · ' . $this->make);
    }

    public function getTypeAttribute(): string
    {
        return $this->bike_type;
    }

    public static function bikeTypes(): array
    {
        return [
            'Mountain Bike',
            'City Bike',
            "Lady's/Men's Bike",
            'E-Scooter',
            'Road Bike',
            'Sidecar Bike',
            "Children's Bike",
        ];
    }

    public static function prefixForType(string $bikeType): string
    {
        $prefix = self::BIKE_TYPE_PREFIXES[strtolower(trim($bikeType))] ?? null;

        if ($prefix === null) {
            throw new \InvalidArgumentException("Unsupported bike type: {$bikeType}");
        }

        return $prefix;
    }

    public static function nextQrCode(string $bikeType): string
    {
        $prefix = self::prefixForType($bikeType);
        $candidate = max(
            (int) DB::table('bicycle_qr_sequences')->where('prefix', $prefix)->value('last_sequence'),
            self::highestExistingSequence($prefix)
        ) + 1;

        do {
            $code = sprintf('%s-%03d', $prefix, $candidate++);
        } while (static::where('qr_code', $code)->exists());

        return $code;
    }

    public static function reserveNextQrCode(string $bikeType): string
    {
        $prefix = self::prefixForType($bikeType);

        return DB::transaction(function () use ($prefix): string {
            DB::table('bicycle_qr_sequences')->insertOrIgnore([
                'prefix' => $prefix,
                'last_sequence' => 0,
            ]);

            $sequence = DB::table('bicycle_qr_sequences')
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            $nextSequence = max(
                (int) $sequence->last_sequence,
                self::highestExistingSequence($prefix)
            ) + 1;

            DB::table('bicycle_qr_sequences')
                ->where('prefix', $prefix)
                ->update(['last_sequence' => $nextSequence]);

            return sprintf('%s-%03d', $prefix, $nextSequence);
        });
    }

    private static function highestExistingSequence(string $prefix): int
    {
        return static::where('qr_code', 'like', $prefix . '-%')
            ->pluck('qr_code')
            ->reduce(function (int $highest, string $code) use ($prefix): int {
                if (preg_match('/\A' . preg_quote($prefix, '/') . '-(\d+)\z/', $code, $matches)) {
                    return max($highest, (int) $matches[1]);
                }

                return $highest;
            }, 0);
    }

    protected $fillable = [
        'qr_code',
        'model',
        'make',
        'bike_type',
        'status',
        'condition',
    ];

    public function rentals()
    {
        return $this->hasMany(Rental::class, 'bike_id');
    }
}