<?php

namespace App\Http\Middleware;

use Closure;
use App\Support\SessionTimeout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminLoggedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        if (SessionTimeout::hasExpired($request)) {
            Auth::guard('web')->logout();
            Auth::guard('staff_admin')->logout();
            SessionTimeout::clear($request);
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->deny($request, 'Your session expired due to inactivity.');
        }

        SessionTimeout::touch($request);

        // 1) Accounts from the admin table.
        $adminGuard = Auth::guard('web');
        if ($adminGuard->check()) {
            $admin = $adminGuard->user();

            if ($this->sessionAuthVersionMatches($request, 'admin_auth_version', (int) ($admin->auth_version ?? 1))) {
                return $next($request);
            }

            $adminGuard->logout();
            Auth::guard('staff_admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->deny($request, 'Your password was changed. Please log in again.');
        }

        // 2) Staff accounts with role "Admin" or "Super Admin". Re-checked on every request so
        //    demoting, deactivating or deleting the staff member revokes access.
        $staffGuard = Auth::guard('staff_admin');
        if ($staffGuard->check()) {
            // Reload so role/status changes apply even if the user was cached.
            $staff = $staffGuard->user()->fresh();

            if ($staff && $staff->hasAdminPortalAccess()) {
                if (!$this->sessionAuthVersionMatches($request, 'staff_admin_auth_version', (int) ($staff->auth_version ?? 1))) {
                    $staffGuard->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return $this->deny($request, 'Your password was changed. Please log in again.');
                }

                $staffGuard->setUser($staff);
                // Make auth()->user() / auth()->id() resolve to this staff admin.
                Auth::shouldUse('staff_admin');

                return $next($request);
            }

            $staffGuard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->deny($request, 'Your account no longer has Admin Portal access.');
        }

        return $this->deny($request, 'Please log in to continue.');
    }

    private function sessionAuthVersionMatches(Request $request, string $key, int $currentVersion): bool
    {
        $sessionVersion = (int) $request->session()->get($key, 1);

        if ($sessionVersion !== $currentVersion) {
            return false;
        }

        $request->session()->put($key, $currentVersion);

        return true;
    }

    private function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Your admin session has expired. Please log in again.'], 401);
        }

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
