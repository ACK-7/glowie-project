<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin Middleware
 * 
 * Ensures only authenticated admin users can access admin endpoints
 * This middleware checks for admin role and active status
 */
class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please login to access admin features.',
                'error_code' => 'AUTH_REQUIRED'
            ], 401);
        }

        // Check if user is an admin
        if (!$this->isAdmin($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required. You do not have permission to access this resource.',
                'error_code' => 'ADMIN_ACCESS_REQUIRED'
            ], 403);
        }

        // Check if admin account is active
        if (isset($user->is_active) && !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your admin account has been deactivated. Please contact the system administrator.',
                'error_code' => 'ACCOUNT_DEACTIVATED'
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
        // Check if user is instance of User model (not Customer)
        if ($user instanceof \App\Models\Customer) {
            return false; // Customers cannot be admins
        }

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

        // Default: if user is User model and not Customer, assume admin
        return $user instanceof \App\Models\User;
    }
}