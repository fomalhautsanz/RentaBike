<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\RateLimiter;

class StaffLoginController extends Controller
{
    /**
     * Maximum failed login attempts.
     */
    private const MAX_ATTEMPTS = 3;

    /**
     * Lockout duration in seconds.
     * 180 seconds = 3 minutes.
     */
    private const LOCKOUT_TIME = 180;


    public function showLogin()
    {
        return view('staff.pages._login');
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
        $key = strtolower($request->email) . '|' . $request->ip();


        /*
        |--------------------------------------------------------------------------
        | Check if Login is Currently Locked
        |--------------------------------------------------------------------------
        */

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {

            $seconds = RateLimiter::availableIn($key);

            $minutes = ceil($seconds / 60);

            return back()
                ->withErrors([
                    'email' => "Too many failed attempts. Please try again in {$minutes} minute(s).",
                ])
                ->onlyInput('email');
        }


        /*
        |--------------------------------------------------------------------------
        | Find Staff Account
        |--------------------------------------------------------------------------
        */

        $staff = Staff::where('email', $request->email)
                      ->where('status', 'active')
                      ->first();


        /*
        |--------------------------------------------------------------------------
        | Check Credentials
        |--------------------------------------------------------------------------
        */

        if ($staff && Hash::check($request->password, $staff->password_hash)) {

            // Successful login → reset failed attempts
            RateLimiter::clear($key);

            // Create staff session
            Session::put('staff_id', $staff->staff_id);
            Session::put('staff_name', $staff->full_name);
            Session::put('staff_role', $staff->role);

            return redirect()->route('staff.home');
        }


        /*
        |--------------------------------------------------------------------------
        | Invalid Credentials
        |--------------------------------------------------------------------------
        */

        RateLimiter::hit($key, self::LOCKOUT_TIME);

        return back()
            ->withErrors([
                'email' => 'Invalid email or password.',
            ])
            ->onlyInput('email');
    }


    public function logout(Request $request)
    {
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