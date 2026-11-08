<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\HashHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 3;
    private const LOCKOUT_SECONDS = 180; // 3 minutes

    public function showLogin()
    {
        $staffAdmin = Auth::guard('staff_admin')->user();
        if (Auth::guard('web')->check() || ($staffAdmin instanceof Staff && $staffAdmin->hasAdminPortalAccess())) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);


        $email = Str::lower(trim($request->input('email')));
        $emailHash = HashHelper::email($email);

        $key = 'admin-login|' . $emailHash . '|' . $request->ip();


        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {

            $seconds = RateLimiter::availableIn($key);

            $minutes = ceil($seconds / 60);

            return back()
                ->withErrors([
                    'email' => "Too many failed attempts. Please try again in {$minutes} minute(s).",
                ])
                ->onlyInput('email');
        }


        $admin = Admin::where('email_hash', $emailHash)->first();

        if ($admin && Hash::check($request->password, $admin->password_hash)) {
            RateLimiter::clear($key);

            Auth::guard('staff_admin')->logout();
            Auth::guard('web')->login(
                $admin,
                $request->boolean('remember')
            );

            $request->session()->regenerate();

            return redirect()->route('admin.dashboard');
        }

        // Staff accounts with role "Admin" also have Admin Portal access.
        $staffAdmin = $admin ? null : Staff::where('email_hash', $emailHash)
            ->adminPortalEligible()
            ->first();

        if ($staffAdmin && Hash::check($request->password, $staffAdmin->password_hash)) {
            RateLimiter::clear($key);

            Auth::guard('web')->logout();
            Auth::guard('staff_admin')->login($staffAdmin);

            $request->session()->regenerate();

            return redirect()->route('admin.dashboard');
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
        Auth::guard('web')->logout();
        Auth::guard('staff_admin')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}