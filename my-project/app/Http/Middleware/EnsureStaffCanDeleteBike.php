<?php

namespace App\Http\Middleware;

use App\Models\Staff;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffCanDeleteBike
{
    public function handle(Request $request, Closure $next): Response
    {
        $permissions = $request->attributes->get('staffPermissions', []);
        abort_unless(
            in_array('Manage Inventory', $permissions, true)
                || in_array('Delete Inventory', $permissions, true),
            403
        );

        $password = $request->input('password');
        $staff = $request->attributes->get('staffAccount');
        $error = !is_string($password) || $password === ''
            ? 'Enter your password to confirm bike deletion.'
            : (!$staff instanceof Staff || !Hash::check($password, $staff->password_hash)
                ? 'Incorrect password. The bike was not deleted.'
                : null);

        if ($error !== null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $error], 422);
            }

            return redirect()->route('staff.home', ['screen' => 'inventory'])
                ->with('success', $error);
        }

        return $next($request);
    }
}
