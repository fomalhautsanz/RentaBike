<?php

namespace App\Support;

use Illuminate\Http\Request;

class SessionTimeout
{
    public const KEY = 'last_activity';
    public const MINUTES = 15;

    public static function hasExpired(Request $request): bool
    {
        $lastActivity = $request->session()->get(self::KEY);

        if (! is_numeric($lastActivity)) {
            self::touch($request);
            return false;
        }

        return (time() - (int) $lastActivity) >= (self::MINUTES * 60);
    }

    public static function touch(Request $request): void
    {
        $request->session()->put(self::KEY, time());
    }

    public static function clear(Request $request): void
    {
        $request->session()->forget(self::KEY);
    }
}
