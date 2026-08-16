<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class StaffLoginController extends Controller
{
    public function showLogin()
    {
        return view('staff.pages._login'); // shows the login screen inside staff.home
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $staff = Staff::where('email', $request->email)
                      ->where('status', 'active')
                      ->first();

        if ($staff && Hash::check($request->password, $staff->password_hash)) {
            Session::put('staff_id',   $staff->staff_id);
            Session::put('staff_name', $staff->full_name);
            Session::put('staff_role', $staff->role);
            return redirect()->route('staff.home');
        }

        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Session::forget(['staff_id', 'staff_name', 'staff_role']);
        return redirect()->route('staff.login');
    }
}