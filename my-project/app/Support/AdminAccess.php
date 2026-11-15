<?php

namespace App\Support;

use App\Models\Staff;
use Illuminate\Support\Facades\Auth;

/**
 * Role-based access sa Admin Portal.
 *
 * - Accounts gikan sa `admin` table: full access.
 * - Staff nga "Admin" ang role: ang mga privileges ra nga naka-check sa ilang account.
 */
class AdminAccess
{
    public static function allows(string $permission): bool
    {
        if (Auth::guard('web')->check()) {
            return true;
        }

        $staff = Auth::guard('staff_admin')->user();

        return $staff instanceof Staff
            && $staff->hasAdminPortalAccess()
            && $staff->hasPermission($permission);
    }
}
