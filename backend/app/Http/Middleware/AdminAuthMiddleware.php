<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin Authentication Middleware
 * 
 * Ensures only authenticated admin users can access admin endpoints
 */
class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        // Check if user has admin role
        if (!$this->isAdmin($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required',
            ], 403);
        }

        // Check if admin account is active
        if (isset($user->is_active) && !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if user has admin privileges
     *
     * @param mixed $user
     * @return bool
     */
    private function isAdmin($user): bool
    {
        // Check if user has role field
        if (isset($user->role)) {
            return in_array($user->role, ['admin', 'super_admin', 'manager']);
        }

        // Fallback: check if user has admin method
        if (method_exists($user, 'isAdmin')) {
            return $user->isAdmin();
        }

        // Fallback: check if user has hasRole method
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('manager');
        }

        return false;
    }
}