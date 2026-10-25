<?php

use App\Models\Bicycle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $bikes = DB::table('bicycle')->orderBy('bike_id')->get();
            $temporaryPrefix = 'TMP-' . Str::uuid() . '-';

            foreach ($bikes as $bike) {
                DB::table('bicycle')
                    ->where('bike_id', $bike->bike_id)
                    ->update(['qr_code' => $temporaryPrefix . $bike->bike_id]);
            }

            $sequenceByType = [];
            foreach ($bikes as $bike) {
                $typeKey = strtolower(trim($bike->bike_type));
                $sequenceByType[$typeKey] = ($sequenceByType[$typeKey] ?? 0) + 1;
                $code = sprintf(
                    '%s-%03d',
                    Bicycle::prefixForType($bike->bike_type),
                    $sequenceByType[$typeKey]
                );

                DB::table('bicycle')
                    ->where('bike_id', $bike->bike_id)
                    ->update(['qr_code' => $code]);
            }
        });
    }

    public function down(): void
    {
        throw new LogicException('Bike QR codes cannot be restored after category-based renumbering.');
    }
};
