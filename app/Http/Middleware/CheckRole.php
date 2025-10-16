<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Check if the user is logged in
        if (!Auth::check()) {
            return redirect('/login');
        }

        // 2. Get the authenticated user
        $user = Auth::user();

        // 3. Check if the user has a role and if that role is in the allowed list ($roles)
        if ($user->role && in_array($user->role->role_name, $roles)) {
            // The user's role is allowed: proceed to the requested page
            return $next($request);
        }

        // 4. If the user is logged in but unauthorized, redirect them
        //    to a safe place (like the dashboard) with an error message.
        return redirect('/dashboard')->with('error', 'Access Denied: You do not have permission to view that page.');
    }
}
