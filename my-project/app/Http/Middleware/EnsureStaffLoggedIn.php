<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Staff;
use App\Support\SessionTimeout;
use App\Support\StaffPortalRemember;
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
        if (SessionTimeout::hasExpired($request)) {
            StaffPortalRemember::forget(
                Staff::find(Session::get('staff_id')) ?? StaffPortalRemember::resolve($request)
            );
            Session::forget(['staff_id', 'staff_name', 'staff_role']);
            SessionTimeout::clear($request);
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Your session expired due to inactivity.']);
        }

        SessionTimeout::touch($request);

        $staff = Session::has('staff_id')
            ? Staff::find(Session::get('staff_id'))
            : null;

        if ($staff && !$this->sessionAuthVersionMatches($request, $staff)) {
            Session::forget(['staff_id', 'staff_name', 'staff_role', 'staff_auth_version']);
            SessionTimeout::clear($request);
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Your password was changed. Please log in again.']);
        }

        // "Remember me": kung expired na ang session, i-restore gikan sa cookie
        if (!$staff && ($remembered = StaffPortalRemember::resolve($request))
            && strtolower((string) $remembered->status) === 'active') {
            $request->session()->regenerate();
            Session::put('staff_id', $remembered->staff_id);
            Session::put('staff_name', trim(($remembered->first_name ?? '') . ' ' . ($remembered->last_name ?? '')) ?: $remembered->full_name);
            Session::put('staff_role', $remembered->role);
            Session::put('staff_auth_version', (int) $remembered->auth_version);
            $staff = $remembered;
        }

        if (!$staff || strtolower((string) $staff->status) !== 'active') {
            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Please log in to continue.']);
        }

        $request->attributes->set('authenticatedStaff', $staff);

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
        if ($staff->isSuperAdmin()) {
            $staffPermissions = array_values(array_unique([
                ...$staffPermissions,
                ...array_values($permissionNames),
            ]));
        }
        if (
            !in_array('View Inventory', $staffPermissions, true)
            && array_intersect(['Add Inventory', 'Edit Inventory', 'Delete Inventory'], $staffPermissions)
        ) {
            $staffPermissions[] = 'View Inventory';
        }
        view()->share('staffPermissions', $staffPermissions);
        $request->attributes->set('staffPermissions', $staffPermissions);

        return $next($request);
    }

    private function sessionAuthVersionMatches(Request $request, Staff $staff): bool
    {
        $sessionVersion = (int) $request->session()->get('staff_auth_version', 1);
        $currentVersion = (int) ($staff->auth_version ?? 1);

        if ($sessionVersion !== $currentVersion) {
            return false;
        }

        $request->session()->put('staff_auth_version', $currentVersion);

        return true;
    }
}