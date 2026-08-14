<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    $admin = \App\Models\Admin::where('email', $request->email)->first();

    if ($admin && \Illuminate\Support\Facades\Hash::check($request->password, $admin->password_hash)) {
        \Illuminate\Support\Facades\Auth::login($admin, $request->boolean('remember'));
        $request->session()->regenerate();
        return redirect()->route('admin.dashboard');
    }

    return back()->withErrors([
        'email' => 'Invalid email or password.',
    ])->onlyInput('email');
}

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}