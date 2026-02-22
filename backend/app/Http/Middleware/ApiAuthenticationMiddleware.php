<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Authentication Middleware
 * 
 * Handles API authentication using Laravel Sanctum tokens
 */
class ApiAuthenticationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Check if user is authenticated via Sanctum
            if (!auth('sanctum')->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please provide a valid API token.',
                ], 401);
            }

            // Set the authenticated user for the request
            $request->setUserResolver(function () {
                return auth('sanctum')->user();
            });

            return $next($request);
        } catch (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage(),
            ], 401);
        }
    }
}