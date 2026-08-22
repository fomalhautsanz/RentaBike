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
    private const MAX_ATTEMPTS = 3;

    private const LOCKOUT_SECONDS = 180;

    public function showLogin()
    {
        return view('staff.pages._login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

       
        $key = strtolower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {

            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            return back()
                ->withErrors([
                    'email' => "Too many failed attempts. Please try again in {$minutes} minute(s).",
                ])
                ->onlyInput('email');
        }

        $staff = Staff::where('email', $request->email)
            ->where('status', 'active')
            ->first();


        if ($staff && Hash::check($request->password, $staff->password_hash)) {

        
            RateLimiter::clear($key);

            Session::put('staff_id', $staff->staff_id);
            Session::put('staff_name', $staff->full_name);
            Session::put('staff_role', $staff->role);

            $request->session()->regenerate();

            return redirect()->route('staff.home');
        }
        
        RateLimiter::hit($key, self::LOCKOUT_SECONDS);

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