<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ActivityLogger;
use App\Support\RentalPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PricingController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $rules = [
            'hourly_rates' => ['required', 'array:city_bike,lady_mens_bike,mountain_bike,sidecar_bike,road_bike,childrens_bike,standard'],
            'scooter_packages' => ['required', 'array:15,30,60'],
            ...collect(RentalPricing::MANAGED_HOURLY_TYPES)
                ->mapWithKeys(fn (string $type, string $key): array => [
                    "hourly_rates.{$key}" => ['required', 'numeric', 'min:0', 'max:100000', 'regex:/^\\d+(\\.\\d{1,2})?$/'],
                ])
                ->all(),
            ...collect(RentalPricing::SCOOTER_PACKAGE_MINUTES)
                ->mapWithKeys(fn (int $minutes): array => [
                    "scooter_packages.{$minutes}" => ['required', 'numeric', 'min:0', 'max:100000', 'regex:/^\\d+(\\.\\d{1,2})?$/'],
                ])
                ->all(),
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->route('admin.dashboard', ['tab' => 'pricing'])
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $before = RentalPricing::adminSettings();
        $changes = [];

        DB::transaction(function () use ($validated, $before, &$changes): void {
            $now = now();

            foreach (RentalPricing::MANAGED_HOURLY_TYPES as $key => $bikeType) {
                $price = (float) $validated['hourly_rates'][$key];
                $changes[$bikeType . ' / hour'] = [
                    'before' => $before['hourly_rates'][$key],
                    'after' => $price,
                ];
                DB::table('rental_price_settings')->updateOrInsert(
                    ['bike_type' => $bikeType, 'duration_minutes' => 60],
                    ['price' => $price, 'updated_at' => $now]
                );
            }

            foreach (RentalPricing::SCOOTER_PACKAGE_MINUTES as $minutes) {
                $price = (float) $validated['scooter_packages'][$minutes];
                $changes['E-Scooter / ' . $minutes . ' min'] = [
                    'before' => $before['scooter_packages'][$minutes],
                    'after' => $price,
                ];
                DB::table('rental_price_settings')->updateOrInsert(
                    ['bike_type' => 'E-Scooter', 'duration_minutes' => $minutes],
                    ['price' => $price, 'updated_at' => $now]
                );
            }
        });

        $changedValues = [];
        foreach ($changes as $label => $change) {
            if ((float) $change['before'] !== (float) $change['after']) {
                $changedValues[$label] = $change;
            }
        }

        if ($changedValues !== []) {
            ActivityLogger::record(
                $request,
                'UPDATE_PRICING',
                'Updated rental pricing | Changes: ' . ActivityLogger::formatChanges($changedValues)
            );
        }

        return redirect()->route('admin.dashboard')
            ->with('active_tab', 'pricing')
            ->with('success', 'Rental pricing updated successfully.');
    }
}
