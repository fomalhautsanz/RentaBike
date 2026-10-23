<?php

use App\Models\Bicycle;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bikes:renumber', function () {
    $bikes = Bicycle::orderBy('created_at')->orderBy('bike_id')->get();

    if ($bikes->isEmpty()) {
        $this->info('No bikes to renumber.');
        return;
    }

    foreach ($bikes->values() as $i => $bike) {
        $this->line(sprintf('%s → RB-%03d', $bike->qr_code, $i + 1));
    }

    if (!$this->confirm('Renumber these bikes? Printed QR stickers with old codes will stop working.')) {
        return;
    }

    DB::transaction(function () use ($bikes) {
        // Temporary codes first so the unique qr_code column never collides mid-rename.
        foreach ($bikes as $bike) {
            $bike->update(['qr_code' => 'TMP-' . $bike->bike_id]);
        }
        foreach ($bikes->values() as $i => $bike) {
            $bike->update(['qr_code' => sprintf('RB-%03d', $i + 1)]);
        }
    });

    $this->info("Renumbered {$bikes->count()} bikes.");
})->purpose('One-time rename of all bike codes to RB-### in creation order');
