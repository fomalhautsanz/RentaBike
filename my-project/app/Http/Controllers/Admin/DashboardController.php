<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_bikes'       => 24,
            'active_rentals'    => 8,
            'under_maintenance' => 3,
            'revenue'           => 19800,
        ];

        $staff    = User::whereNotNull('role')->orderBy('name')->get();
        $bikes    = collect([]);
        $reports  = collect([]);
        $rentals  = collect([]);

        $pendingReports    = 2;
        $inProgressReports = 1;
        $resolvedReports   = 5;

        return view('admin.dashboard', compact(
            'stats', 'staff', 'bikes', 'reports', 'rentals',
            'pendingReports', 'inProgressReports', 'resolvedReports'
        ));
    }

    public function storeStaff(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'string', 'in:Staff,Manager,Technician'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $email = Str::lower(trim($validated['email']));

        if (User::where('email_hash', User::emailLookupHash($email))->exists()) {
            return back()->withErrors(['email' => 'That email address is already registered.'])->withInput();
        }

        User::create([
            'name' => $validated['name'],
            'email' => $email,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => $validated['password'],
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Staff member added successfully.');
    }
}