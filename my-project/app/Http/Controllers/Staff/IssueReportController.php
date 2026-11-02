<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bicycle;
use App\Models\IssueReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class IssueReportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'bike_code' => strtoupper(trim((string) $request->input('bike_code'))),
        ]);

        $validator = Validator::make($request->all(), [
            'bike_code' => ['required', 'string', 'max:100', 'exists:bicycle,qr_code'],
            'issue_type' => ['required', 'in:damage,missing,other'],
            'description' => ['required', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ], [
            'bike_code.exists' => 'Enter a bike ID that exists in the inventory.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('staff.home', ['screen' => 'report-form'])
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $bike = Bicycle::where('qr_code', $validated['bike_code'])->firstOrFail();
        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('issue-reports', 'public');

            if ($photoPath === false) {
                throw new RuntimeException('Unable to store the issue report photo.');
            }
        }

        DB::transaction(function () use ($bike, $validated, $request, $photoPath): void {
            IssueReport::create([
                'bike_id' => $bike->bike_id,
                'staff_id' => (int) $request->session()->get('staff_id'),
                'issue_type' => $validated['issue_type'],
                'description' => $validated['description'],
                'photo_ref' => $photoPath,
            ]);

            if (in_array($validated['issue_type'], ['damage', 'missing'], true)) {
                $bike->update([
                    'condition' => $validated['issue_type'] === 'missing' ? 'missing' : 'repair',
                    'status' => 'repair',
                ]);
            }
        });

        return redirect()
            ->route('staff.home', ['screen' => 'report'])
            ->with('success', 'Issue report submitted successfully.');
    }
}
