<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bike extends Model
{
    protected $fillable = [
        'bike_code',
        'name',
        'type',
        'qr_code',
        'status',
        'condition',
    ];

    protected function casts(): array
    {
        return [
            'last_maintenance' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'bike_code';
    }

    protected static function booted(): void
    {
        static::creating(function (Bike $bike): void {
            if ($bike->bike_code === null) {
                $lastNumber = Bike::query()
                    ->pluck('bike_code')
                    ->map(fn (string $code): int => (int) preg_replace('/^BK-/', '', $code))
                    ->max() ?? 0;

                $bike->bike_code = 'BK-' . str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);
            }

            $bike->qr_code ??= $bike->bike_code;
        });
    }
}
