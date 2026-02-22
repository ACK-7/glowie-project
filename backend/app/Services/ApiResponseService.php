<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * API Response Service
 * 
 * Centralized service for consistent API response formatting
 */
class ApiResponseService
{
    /**
     * Success response format
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function success($data = null, string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message ?? 'Operation completed successfully',
            'timestamp' => now()->toISOString(),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response format
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $errors
     * @param string|null $errorCode
     * @return JsonResponse
     */
    public static function error(
        string $message, 
        int $statusCode = 400, 
        array $errors = null, 
        string $errorCode = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Paginated response format
     *
     * @param LengthAwarePaginator $paginator
     * @param string|null $message
     * @return JsonResponse
     */
    public static function paginated(LengthAwarePaginator $paginator, string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? 'Data retrieved successfully',
            'data' => $paginator->items(),
            'meta' => [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'has_more_pages' => $paginator->hasMorePages(),
                    'path' => $paginator->path(),
                    'links' => [
                        'first' => $paginator->url(1),
                        'last' => $paginator->url($paginator->lastPage()),
                        'prev' => $paginator->previousPageUrl(),
                        'next' => $paginator->nextPageUrl(),
                    ],
                ]
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Collection response format
     *
     * @param Collection|array $collection
     * @param string|null $message
     * @param array $meta
     * @return JsonResponse
     */
    public static function collection($collection, string $message = null, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message ?? 'Data retrieved successfully',
            'data' => $collection,
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response);
    }

    /**
     * Created response format (201)
     *
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    public static function created($data = null, string $message = null): JsonResponse
    {
        return self::success($data, $message ?? 'Resource created successfully', 201);
    }

    /**
     * Updated response format (200)
     *
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    public static function updated($data = null, string $message = null): JsonResponse
    {
        return self::success($data, $message ?? 'Resource updated successfully', 200);
    }

    /**
     * Deleted response format (200)
     *
     * @param string|null $message
     * @return JsonResponse
     */
    public static function deleted(string $message = null): JsonResponse
    {
        return self::success(null, $message ?? 'Resource deleted successfully', 200);
    }

    /**
     * Not found response format (404)
     *
     * @param string $resource
     * @return JsonResponse
     */
    public static function notFound(string $resource = 'Resource'): JsonResponse
    {
        return self::error("{$resource} not found", 404, null, 'RESOURCE_NOT_FOUND');
    }

    /**
     * Validation error response format (422)
     *
     * @param array $errors
     * @param string|null $message
     * @return JsonResponse
     */
    public static function validationError(array $errors, string $message = null): JsonResponse
    {
        return self::error(
            $message ?? 'Validation failed',
            422,
            $errors,
            'VALIDATION_ERROR'
        );
    }

    /**
     * Unauthorized response format (401)
     *
     * @param string|null $message
     * @return JsonResponse
     */
    public static function unauthorized(string $message = null): JsonResponse
    {
        return self::error(
            $message ?? 'Unauthorized access',
            401,
            null,
            'UNAUTHORIZED'
        );
    }

    /**
     * Forbidden response format (403)
     *
     * @param string|null $message
     * @return JsonResponse
     */
    public static function forbidden(string $message = null): JsonResponse
    {
        return self::error(
            $message ?? 'Access forbidden',
            403,
            null,
            'FORBIDDEN'
        );
    }

    /**
     * Server error response format (500)
     *
     * @param string|null $message
     * @return JsonResponse
     */
    public static function serverError(string $message = null): JsonResponse
    {
        return self::error(
            $message ?? 'Internal server error',
            500,
            null,
            'SERVER_ERROR'
        );
    }

    /**
     * Conflict response format (409)
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function conflict(string $message): JsonResponse
    {
        return self::error($message, 409, null, 'CONFLICT');
    }

    /**
     * Too many requests response format (429)
     *
     * @param string|null $message
     * @return JsonResponse
     */
    public static function tooManyRequests(string $message = null): JsonResponse
    {
        return self::error(
            $message ?? 'Too many requests',
            429,
            null,
            'TOO_MANY_REQUESTS'
        );
    }
}