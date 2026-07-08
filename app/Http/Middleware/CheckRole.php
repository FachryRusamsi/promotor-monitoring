<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Verify that the authenticated user has one of the allowed roles.
     * If not, redirect them to their role-appropriate dashboard.
     *
     * Usage in routes:
     *   ->middleware('role:admin')
     *   ->middleware('role:promotor')
     *   ->middleware('role:admin,promotor')
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles  One or more role names (case-insensitive)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // If user is not authenticated, let auth middleware handle it
        if (!$user) {
            return redirect()->route('login');
        }

        // Load role relationship if not already loaded
        $user->loadMissing('role');

        $userRoleName = strtolower($user->role->name ?? '');

        // Normalize the allowed roles to lowercase for comparison
        $allowedRoles = array_map('strtolower', $roles);

        // If the user's role is in the allowed list, proceed
        if (in_array($userRoleName, $allowedRoles)) {
            return $next($request);
        }

        // Redirect to the appropriate dashboard based on user role
        return match ($userRoleName) {
            'admin'    => redirect()->route('admin.dashboard'),
            'promotor' => redirect()->route('promotor.dashboard'),
            default    => redirect()->route('login'),
        };
    }
}
