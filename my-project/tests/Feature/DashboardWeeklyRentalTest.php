<?php

use App\Http\Controllers\Admin\DashboardController;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('rental');

    Schema::create('rental', function (Blueprint $table) {
        $table->increments('rental_id');
        $table->dateTime('start_time');
    });
});

test('weekly rental counts use the current Monday boundary and exclude future rentals', function () {
    DB::table('rental')->insert([
        ['start_time' => '2026-11-16 12:00:00'],
        ['start_time' => '2026-11-23 03:00:00'],
        ['start_time' => '2026-11-23 05:00:00'],
        ['start_time' => '2026-11-29 12:00:00'],
    ]);

    $controller = new class extends DashboardController {
        public function weeklyCounts(Carbon $now): array
        {
            return $this->weeklyRentalCounts($now);
        }
    };
    $result = $controller->weeklyCounts(Carbon::parse('2026-11-23 04:30:00', 'Asia/Manila'));

    expect($result['period'])->toContain('Week 48')
        ->and($result['dates'][0])->toBe('November 23, 2026')
        ->and($result['dates'][6])->toBe('November 29, 2026')
        ->and($result['data'])->toHaveCount(7)
        ->and($result['data'][0])->toBe(1)
        ->and(array_sum($result['data']))->toBe(1);
});
