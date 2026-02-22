<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Test API Controller
 * 
 * Simple controller to test the base API infrastructure
 */
class TestApiController extends BaseApiController
{
    /**
     * Test successful response
     */
    public function testSuccess(): JsonResponse
    {
        return $this->successResponse([
            'message' => 'Base API infrastructure is working correctly',
            'timestamp' => now(),
            'features' => [
                'BaseApiController' => 'Implemented',
                'ApiResponseService' => 'Implemented',
                'Sanctum Authentication' => 'Configured',
                'API Middleware Stack' => 'Configured',
                'Consistent Response Format' => 'Implemented',
                'Activity Logging' => 'Implemented',
            ]
        ], 'API infrastructure test successful');
    }

    /**
     * Test error response
     */
    public function testError(): JsonResponse
    {
        return $this->errorResponse('This is a test error response', 400);
    }

    /**
     * Test validation error
     */
    public function testValidation(Request $request): JsonResponse
    {
        // Manually validate to ensure we return 422 status code
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'required_field' => 'required|string',
            'email_field' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()->toArray(),
                'VALIDATION_ERROR'
            );
        }

        return $this->successResponse(['message' => 'Validation passed']);
    }

    /**
     * Test authentication (requires auth)
     */
    public function testAuth(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        return $this->successResponse([
            'user_id' => $user->id,
            'user_name' => $user->name ?? $user->first_name . ' ' . $user->last_name,
            'is_admin' => $this->isAdmin(),
        ], 'Authentication test successful');
    }

    /**
     * Test admin access (requires admin role)
     */
    public function testAdmin(): JsonResponse
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin access required');
        }

        return $this->successResponse([
            'admin_access' => true,
            'user' => $this->getAuthenticatedUser(),
        ], 'Admin access test successful');
    }

    /**
     * Test activity logging
     */
    public function testLogging(): JsonResponse
    {
        $this->logActivity('test_action', 'TestModel', 123, [
            'test_field' => 'test_value',
            'timestamp' => now(),
        ]);

        return $this->successResponse([
            'logged' => true,
            'message' => 'Activity logged successfully',
        ], 'Logging test successful');
    }

    /**
     * Test paginated response
     */
    public function testPagination(): JsonResponse
    {
        // Create fake paginated data for testing
        $data = collect(range(1, 100))->map(function ($i) {
            return ['id' => $i, 'name' => "Item {$i}"];
        });

        $perPage = 10;
        $currentPage = request('page', 1);
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $data->forPage($currentPage, $perPage),
            $data->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );

        return $this->paginatedResponse($paginator, 'Pagination test successful');
    }

    /**
     * Test collection response
     */
    public function testCollection(): JsonResponse
    {
        $collection = collect([
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
            ['id' => 3, 'name' => 'Item 3'],
        ]);

        return $this->collectionResponse($collection, 'Collection test successful', [
            'total_items' => $collection->count(),
            'test_meta' => 'This is test metadata',
        ]);
    }
}