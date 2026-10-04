<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminLoggedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your admin session has expired. Please log in again.'], 401);
            }

            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Please log in to continue.',
                ]);
        }

        return $next($request);
    }
}