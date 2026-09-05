<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffLoggedIn
{
    /**
     * Checks the custom staff session (set in StaffLoginController::login)
     * rather than Laravel's built-in auth guard, since staff accounts
     * aren't authenticated through Auth::attempt().
     */
    public function handle(Request $request, Closure $next): Response
    {
        $staff = Session::has('staff_id')
            ? Staff::find(Session::get('staff_id'))
            : null;

        if (!$staff || strtolower((string) $staff->status) !== 'active') {
            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Please log in to continue.']);
        }

        $permissionNames = [
            'view inventory' => 'View Inventory',
            'add inventory' => 'Add Inventory',
            'edit inventory' => 'Edit Inventory',
            'delete inventory' => 'Delete Inventory',
            'manage staff' => 'Manage Staff',
            'handle maintenance' => 'Handle Maintenance',
        ];
        $staffPermissions = collect(is_array($staff->permissions) ? $staff->permissions : [])
            ->map(fn ($permission) => $permissionNames[strtolower(trim((string) $permission))] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
        view()->share('staffPermissions', $staffPermissions);

        if (in_array('Manage Staff', $staffPermissions, true)) {
            view()->share('staffMembers', Staff::orderBy('staff_id')->get());
        }

        return $next($request);
    }
}