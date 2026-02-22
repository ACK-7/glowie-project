<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Broadcast;
use App\Services\RealTimeService;

/**
 * Broadcast Controller
 * 
 * Handles WebSocket authentication and real-time broadcasting endpoints
 */
class BroadcastController extends Controller
{
    public function __construct(
        private RealTimeService $realTimeService
    ) {}

    /**
     * Authenticate the request for broadcasting
     */
    public function authenticate(Request $request)
    {
        return Broadcast::auth($request);
    }

    /**
     * Get current dashboard statistics for real-time updates
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            $stats = $this->realTimeService->getDashboardStats();
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard statistics',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Force refresh dashboard statistics
     */
    public function refreshDashboardStats(Request $request): JsonResponse
    {
        try {
            // Clear cache and recalculate
            $this->realTimeService->clearDashboardStatsCache();
            $this->realTimeService->updateDashboardStats(['all'], 'manual.refresh');
            
            $stats = $this->realTimeService->getDashboardStats();
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard statistics refreshed successfully',
                'data' => $stats,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh dashboard statistics',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Test real-time broadcasting (development only)
     */
    public function testBroadcast(Request $request): JsonResponse
    {
        if (!app()->environment('local')) {
            return response()->json([
                'success' => false,
                'message' => 'Test broadcasting is only available in local environment',
            ], 403);
        }

        try {
            $testData = [
                'message' => 'Test broadcast message',
                'timestamp' => now()->toISOString(),
                'user' => $request->user()?->name ?? 'Anonymous',
            ];

            $this->realTimeService->broadcastCustomNotification(
                ['admin.dashboard'],
                'test.broadcast',
                $testData
            );

            return response()->json([
                'success' => true,
                'message' => 'Test broadcast sent successfully',
                'data' => $testData,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test broadcast',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}