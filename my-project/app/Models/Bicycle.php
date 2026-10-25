<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public static function nextQrCode(string $bikeType, ?int $excludeBikeId = null): string
    {
        $prefix = self::prefixForType($bikeType);
        $bikes = static::whereRaw('LOWER(TRIM(bike_type)) = ?', [strtolower(trim($bikeType))]);

        if ($excludeBikeId !== null) {
            $bikes->where('bike_id', '!=', $excludeBikeId);
        }

        $candidate = $bikes->count() + 1;
        do {
            $code = sprintf('%s-%03d', $prefix, $candidate++);
            $existing = static::where('qr_code', $code);

            if ($excludeBikeId !== null) {
                $existing->where('bike_id', '!=', $excludeBikeId);
            }
        } while ($existing->exists());

        return $code;
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