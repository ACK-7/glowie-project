<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Validation Middleware
 * 
 * Handles global API request validation and formatting
 */
class ApiValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validate Content-Type for POST/PUT/PATCH requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $contentType = $request->header('Content-Type');
            
            // Allow application/json and multipart/form-data (for file uploads)
            if (!str_contains($contentType, 'application/json') && 
                !str_contains($contentType, 'multipart/form-data')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Content-Type. Expected application/json or multipart/form-data.',
                ], 400);
            }
        }

        // Validate Accept header
        $acceptHeader = $request->header('Accept');
        if ($acceptHeader && !str_contains($acceptHeader, 'application/json') && $acceptHeader !== '*/*') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Accept header. Expected application/json.',
            ], 406);
        }

        try {
            $response = $next($request);

            // Ensure all API responses have consistent headers
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $response->headers->set('Content-Type', 'application/json');
                $response->headers->set('X-API-Version', '1.0');
                $response->headers->set('X-Response-Time', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
            }

            return $response;
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}