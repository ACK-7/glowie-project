<?php

namespace App\Http\Controllers;

use App\Services\ApiResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Base API Controller with standardized response methods
 * 
 * This controller provides consistent API response formatting across all endpoints
 * and implements common functionality for authentication, validation, and logging.
 */
abstract class BaseApiController extends Controller
{
    /**
     * Return a successful response with data
     *
     * @param mixed $data The response data
     * @param string|null $message Optional success message
     * @param int $code HTTP status code (default: 200)
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = null, int $code = 200): JsonResponse
    {
        return ApiResponseService::success($data, $message, $code);
    }

    /**
     * Return an error response
     *
     * @param string $message Error message
     * @param int $code HTTP status code (default: 400)
     * @param array|null $errors Detailed error information
     * @param string|null $errorCode Optional error code
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $code = 400, array $errors = null, string $errorCode = null): JsonResponse
    {
        return ApiResponseService::error($message, $code, $errors, $errorCode);
    }

    /**
     * Return a paginated response
     *
     * @param LengthAwarePaginator $paginator Laravel paginator instance
     * @param string|null $message Optional success message
     * @return JsonResponse
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = null): JsonResponse
    {
        return ApiResponseService::paginated($paginator, $message);
    }

    /**
     * Return a collection response with optional metadata
     *
     * @param Collection|array $collection Data collection
     * @param string|null $message Optional success message
     * @param array $meta Optional metadata
     * @return JsonResponse
     */
    protected function collectionResponse($collection, string $message = null, array $meta = []): JsonResponse
    {
        return ApiResponseService::collection($collection, $message, $meta);
    }

    /**
     * Return a created response (201)
     *
     * @param mixed $data Created resource data
     * @param string|null $message Optional success message
     * @return JsonResponse
     */
    protected function createdResponse($data = null, string $message = null): JsonResponse
    {
        return ApiResponseService::created($data, $message);
    }

    /**
     * Return an updated response (200)
     *
     * @param mixed $data Updated resource data
     * @param string|null $message Optional success message
     * @return JsonResponse
     */
    protected function updatedResponse($data = null, string $message = null): JsonResponse
    {
        return ApiResponseService::updated($data, $message);
    }

    /**
     * Return a deleted response (200)
     *
     * @param string|null $message Optional success message
     * @return JsonResponse
     */
    protected function deletedResponse(string $message = null): JsonResponse
    {
        return ApiResponseService::deleted($message);
    }

    /**
     * Return a validation error response
     *
     * @param ValidationException $exception Validation exception
     * @return JsonResponse
     */
    protected function validationErrorResponse(ValidationException $exception): JsonResponse
    {
        return ApiResponseService::validationError($exception->errors());
    }

    /**
     * Return a not found response
     *
     * @param string $resource Resource name (e.g., 'Booking', 'Customer')
     * @return JsonResponse
     */
    protected function notFoundResponse(string $resource = 'Resource'): JsonResponse
    {
        return ApiResponseService::notFound($resource);
    }

    /**
     * Return an unauthorized response
     *
     * @param string|null $message Optional custom message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = null): JsonResponse
    {
        return ApiResponseService::unauthorized($message);
    }

    /**
     * Return a forbidden response
     *
     * @param string|null $message Optional custom message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = null): JsonResponse
    {
        return ApiResponseService::forbidden($message);
    }

    /**
     * Return a server error response
     *
     * @param string|null $message Optional custom message
     * @param Exception|null $exception Optional exception for logging
     * @return JsonResponse
     */
    protected function serverErrorResponse(string $message = null, Exception $exception = null): JsonResponse
    {
        if ($exception) {
            Log::error('Server Error', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'request_url' => request()->fullUrl(),
                'request_method' => request()->method(),
                'user_id' => auth()->id(),
            ]);
        }

        return ApiResponseService::serverError($message);
    }

    /**
     * Return a conflict response
     *
     * @param string $message Conflict message
     * @return JsonResponse
     */
    protected function conflictResponse(string $message): JsonResponse
    {
        return ApiResponseService::conflict($message);
    }

    /**
     * Handle common API exceptions and return appropriate responses
     *
     * @param Exception $exception
     * @return JsonResponse
     */
    protected function handleException(Exception $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return $this->validationErrorResponse($exception);
        }

        // Log unexpected exceptions
        Log::error('Unhandled API Exception', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'request_url' => request()->fullUrl(),
            'request_method' => request()->method(),
            'user_id' => auth()->id(),
        ]);

        return $this->serverErrorResponse();
    }

    /**
     * Validate request data against rules
     *
     * @param Request $request
     * @param array $rules
     * @param array $messages
     * @return array Validated data
     * @throws ValidationException
     */
    protected function validateRequest(Request $request, array $rules, array $messages = []): array
    {
        return $request->validate($rules, $messages);
    }

    /**
     * Get the authenticated user
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function getAuthenticatedUser()
    {
        return auth('sanctum')->user();
    }

    /**
     * Check if the authenticated user has a specific role
     *
     * @param string $role
     * @return bool
     */
    protected function hasRole(string $role): bool
    {
        $user = $this->getAuthenticatedUser();
        return $user && method_exists($user, 'hasRole') && $user->hasRole($role);
    }

    /**
     * Check if the authenticated user has admin privileges
     *
     * @return bool
     */
    protected function isAdmin(): bool
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) return false;

        // Check role field directly
        if (isset($user->role)) {
            return in_array($user->role, ['admin', 'super_admin', 'manager']);
        }

        // Check methods
        return (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
               (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super_admin')));
    }

    /**
     * Log API activity for audit trail
     *
     * @param string $action
     * @param string|null $modelType
     * @param int|null $modelId
     * @param array $changes
     * @return void
     */
    protected function logActivity(string $action, string $modelType = null, int $modelId = null, array $changes = []): void
    {
        try {
            $user = $this->getAuthenticatedUser();
            
            Log::info('API Activity', [
                'user_id' => $user ? $user->id : null,
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'changes' => $changes,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now(),
            ]);

            // If ActivityLog model exists, create a database record
            if (class_exists('\App\Models\ActivityLog')) {
                \App\Models\ActivityLog::create([
                    'user_id' => $user ? $user->id : null,
                    'action' => $action,
                    'model_type' => $modelType,
                    'model_id' => $modelId,
                    'changes' => $changes,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        } catch (Exception $e) {
            // Don't let logging failures break the API
            Log::error('Failed to log activity', [
                'error' => $e->getMessage(),
                'action' => $action,
            ]);
        }
    }
}