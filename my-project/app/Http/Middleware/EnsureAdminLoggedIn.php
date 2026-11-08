<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminLoggedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1) Accounts from the admin table.
        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        // 2) Staff accounts with role "Admin". Re-checked on every request so
        //    demoting, deactivating or deleting the staff member revokes access.
        $staffGuard = Auth::guard('staff_admin');
        if ($staffGuard->check()) {
            // Reload so role/status changes apply even if the user was cached.
            $staff = $staffGuard->user()->fresh();

            if ($staff && $staff->hasAdminPortalAccess()) {
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

    private function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Your admin session has expired. Please log in again.'], 401);
        }

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
