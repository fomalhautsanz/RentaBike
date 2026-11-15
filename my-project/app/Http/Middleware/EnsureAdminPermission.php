<?php

namespace App\Http\Middleware;

use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gamit: ->middleware('admin.can:Manage Staff')
 * I-block ang request kung wala ang privilege, bisan i-type direkta ang URL.
 */
class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (AdminAccess::allows($permission)) {
            return $next($request);
        }

        $message = "You do not have the \"{$permission}\" privilege.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->route('admin.dashboard')->withErrors(['permission' => $message]);
    }
}
