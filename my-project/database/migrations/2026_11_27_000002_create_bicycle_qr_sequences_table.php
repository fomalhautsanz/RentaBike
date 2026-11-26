<?php

use App\Models\Bicycle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bicycle_qr_sequences', function (Blueprint $table) {
            $table->string('prefix', 2)->primary();
            $table->unsignedInteger('last_sequence')->default(0);
        });

        $bikeTypes = [...Bicycle::bikeTypes(), 'Standard'];
        $sequences = [];
        foreach ($bikeTypes as $bikeType) {
            $sequences[Bicycle::prefixForType($bikeType)] = 0;
        }

        $recordCode = static function (?string $code) use (&$sequences): void {
            if (preg_match_all('/\b([A-Z]{2})-(\d+)\b/', (string) $code, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    if (array_key_exists($match[1], $sequences)) {
                        $sequences[$match[1]] = max($sequences[$match[1]], (int) $match[2]);
                    }
                }
            }
        };

        foreach (DB::table('bicycle')->pluck('qr_code') as $code) {
            $recordCode($code);
        }

        if (Schema::hasTable('activity_log')) {
            foreach (DB::table('activity_log')->whereNotNull('details')->orderBy('log_id')->cursor() as $log) {
                $recordCode($log->details);
            }
        }

        $rows = [];
        foreach ($sequences as $prefix => $lastSequence) {
            $rows[] = ['prefix' => $prefix, 'last_sequence' => $lastSequence];
        }
        DB::table('bicycle_qr_sequences')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('bicycle_qr_sequences');
    }
};
