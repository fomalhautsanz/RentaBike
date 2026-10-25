<?php

namespace App\Http\Middleware;

use Closure;
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
        if (! Session::has('staff_id')) {
            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Please log in to continue.']);
        }

        return $next($request);
    }
}