<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\HashHelper;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Support\SessionTimeout;
use App\Support\StaffPortalRemember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class StaffLoginController extends Controller
{
    private const MAX_ATTEMPTS = 3;

    private const LOCKOUT_SECONDS = 180;

    public function showLogin()
    {
        return view('staff.login');
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Rate Limiting Key
        |--------------------------------------------------------------------------
        |
        | The email + IP address combination gets its own attempt counter.
        |
        */
        $email = Str::lower(trim($request->input('email')));
        $emailHash = HashHelper::email($email);

        $key = 'staff-login|' . $emailHash . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {

            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            return back()
                ->withErrors([
                    'email' => "Too many failed attempts. Please try again in {$minutes} minute(s).",
                ])
                ->onlyInput('email');
        }

        $staff = Staff::where('email_hash', $emailHash)
            ->where('status', 'active')
            ->first();

        if ($staff && Hash::check($request->password, $staff->password_hash)) {

        
            RateLimiter::clear($key);

            // Security: bag-ong session ID human mag-login (session fixation)
            $request->session()->regenerate();
            SessionTimeout::touch($request);

            Session::put('staff_id', $staff->staff_id);
            Session::put('staff_name', trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) ?: $staff->full_name);
            Session::put('staff_role', $staff->role);

            if ($request->boolean('remember')) {
                StaffPortalRemember::issue($staff);
            }

            return redirect()->route('staff.home');
        }

        RateLimiter::hit($key, self::LOCKOUT_SECONDS);

        return back()
            ->withErrors(['email' => 'Invalid email or password.'])
            ->onlyInput('email');
    }


    public function logout(Request $request)
    {
        // I-clear ang "remember me" token ug cookie
        StaffPortalRemember::forget(
            Staff::find(Session::get('staff_id')) ?? StaffPortalRemember::resolve($request)
        );

        Session::forget([
            'staff_id',
            'staff_name',
            'staff_role',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }
}