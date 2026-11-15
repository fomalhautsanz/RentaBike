<?php

namespace App\Support;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * "Remember me" para sa staff app (custom session, dili Laravel guard).
 *
 * Cookie: "staff_remember" = "{staff_id}|{random token}" (encrypted ug HttpOnly sa Laravel).
 * Database: sha256 hash ra sa token ang gi-save, para bisan ma-leak ang DB dili magamit.
 */
class StaffPortalRemember
{
    public const COOKIE = 'staff_remember';
    public const MINUTES = 43200; // 30 days

    public static function issue(Staff $staff): void
    {
        $token = Str::random(60);

        $staff->forceFill(['portal_remember_token' => hash('sha256', $token)])->saveQuietly();

        Cookie::queue(Cookie::make(self::COOKIE, $staff->staff_id . '|' . $token, self::MINUTES));
    }

    /** Ibalik ang staff gikan sa cookie; null kung invalid/expired/deactivated. */
    public static function resolve(Request $request): ?Staff
    {
        $value = $request->cookie(self::COOKIE);
        if (! is_string($value) || ! str_contains($value, '|')) {
            return null;
        }

        [$id, $token] = explode('|', $value, 2);
        if (! ctype_digit($id) || $token === '') {
            return null;
        }

        $staff = Staff::find((int) $id);
        if (! $staff || ! $staff->portal_remember_token
            || ! hash_equals($staff->portal_remember_token, hash('sha256', $token))) {
            return null;
        }

        return $staff;
    }

    public static function forget(?Staff $staff): void
    {
        $staff?->forceFill(['portal_remember_token' => null])->saveQuietly();

        Cookie::queue(Cookie::forget(self::COOKIE));
    }
}
