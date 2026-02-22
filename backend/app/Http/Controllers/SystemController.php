<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * System Controller for Configuration Management
 * 
 * Handles system settings management with validation, system health monitoring,
 * performance metrics, and configuration history with rollback capabilities.
 */
class SystemController extends BaseApiController
{
    /**
     * Get all system settings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function settings(Request $request): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('settings.view')) {
                return $this->forbiddenResponse('You do not have permission to view system settings');
            }

            $query = SystemSetting::query();

            // Apply filters
            if ($request->filled('category')) {
                $category = $request->category;
                $query->where('key_name', 'like', "{$category}.%");
            }

            if ($request->filled('is_public')) {
                $query->where('is_public', $request->boolean('is_public'));
            }

            if ($request->filled('data_type')) {
                $query->where('data_type', $request->data_type);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('key_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'key_name');
            $sortOrder = $request->get('sort_order', 'asc');
            
            if (in_array($sortBy, ['key_name', 'data_type', 'is_public', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Get paginated results
            $perPage = min($request->get('per_page', 50), 100);
            $settings = $query->paginate($perPage);

            // Transform data
            $settings->getCollection()->transform(function ($setting) {
                return [
                    'id' => $setting->id,
                    'key_name' => $setting->key_name,
                    'value' => $setting->typed_value,
                    'raw_value' => $setting->value,
                    'data_type' => $setting->data_type,
                    'description' => $setting->description,
                    'is_public' => $setting->is_public,
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ];
            });

            $this->logActivity('viewed_system_settings');

            return $this->paginatedResponse($settings, 'System settings retrieved successfully');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get a specific system setting
     *
     * @param string $key
     * @return JsonResponse
     */
    public function getSetting(string $key): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('settings.view')) {
                return $this->forbiddenResponse('You do not have permission to view system settings');
            }

            $setting = SystemSetting::where('key_name', $key)->first();

            if (!$setting) {
                return $this->notFoundResponse('System setting');
            }

            $this->logActivity('viewed_setting', SystemSetting::class, $setting->id, ['key' => $key]);

            return $this->successResponse([
                'id' => $setting->id,
                'key_name' => $setting->key_name,
                'value' => $setting->typed_value,
                'raw_value' => $setting->value,
                'data_type' => $setting->data_type,
                'description' => $setting->description,
                'is_public' => $setting->is_public,
                'created_at' => $setting->created_at,
                'updated_at' => $setting->updated_at,
            ], 'System setting retrieved successfully');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update system settings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('settings.edit')) {
                return $this->forbiddenResponse('You do not have permission to edit system settings');
            }

            // Validate request
            $validatedData = $this->validateRequest($request, [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string|max:100',
                'settings.*.value' => 'nullable',
                'settings.*.description' => 'nullable|string|max:1000',
                'settings.*.is_public' => 'boolean',
            ]);

            DB::beginTransaction();

            try {
                $updatedSettings = [];
                $changes = [];

                foreach ($validatedData['settings'] as $settingData) {
                    $key = $settingData['key'];
                    $value = $settingData['value'] ?? null;
                    $description = $settingData['description'] ?? null;
                    $isPublic = $settingData['is_public'] ?? false;

                    // Get existing setting for change tracking
                    $existingSetting = SystemSetting::where('key_name', $key)->first();
                    $oldValue = $existingSetting ? $existingSetting->typed_value : null;

                    // Update or create setting
                    $setting = SystemSetting::set($key, $value, $description, $isPublic);

                    $updatedSettings[] = [
                        'id' => $setting->id,
                        'key_name' => $setting->key_name,
                        'value' => $setting->typed_value,
                        'data_type' => $setting->data_type,
                        'description' => $setting->description,
                        'is_public' => $setting->is_public,
                        'updated_at' => $setting->updated_at,
                    ];

                    // Track changes
                    if ($oldValue !== $setting->typed_value) {
                        $changes[$key] = [
                            'old' => $oldValue,
                            'new' => $setting->typed_value,
                        ];
                    }
                }

                // Log activity
                $this->logActivity('updated_system_settings', null, null, $changes);

                DB::commit();

                return $this->successResponse([
                    'updated_settings' => $updatedSettings,
                    'changes_count' => count($changes),
                ], 'System settings updated successfully');

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Reset settings to defaults
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetToDefaults(Request $request): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('settings.edit')) {
                return $this->forbiddenResponse('You do not have permission to reset system settings');
            }

            // Validate request
            $validatedData = $this->validateRequest($request, [
                'keys' => 'nullable|array',
                'keys.*' => 'string',
                'reset_all' => 'boolean',
            ]);

            DB::beginTransaction();

            try {
                $defaults = SystemSetting::getDefaultSettings();
                $resetSettings = [];
                $changes = [];

                if ($validatedData['reset_all'] ?? false) {
                    // Reset all settings to defaults
                    foreach ($defaults as $key => $config) {
                        $existingSetting = SystemSetting::where('key_name', $key)->first();
                        $oldValue = $existingSetting ? $existingSetting->typed_value : null;

                        $setting = SystemSetting::set(
                            $key,
                            $config['value'],
                            $config['description'],
                            $config['is_public']
                        );

                        $resetSettings[] = [
                            'key_name' => $setting->key_name,
                            'value' => $setting->typed_value,
                            'old_value' => $oldValue,
                        ];

                        if ($oldValue !== $setting->typed_value) {
                            $changes[$key] = [
                                'old' => $oldValue,
                                'new' => $setting->typed_value,
                            ];
                        }
                    }
                } else {
                    // Reset specific settings
                    $keys = $validatedData['keys'] ?? [];
                    foreach ($keys as $key) {
                        if (isset($defaults[$key])) {
                            $existingSetting = SystemSetting::where('key_name', $key)->first();
                            $oldValue = $existingSetting ? $existingSetting->typed_value : null;

                            $config = $defaults[$key];
                            $setting = SystemSetting::set(
                                $key,
                                $config['value'],
                                $config['description'],
                                $config['is_public']
                            );

                            $resetSettings[] = [
                                'key_name' => $setting->key_name,
                                'value' => $setting->typed_value,
                                'old_value' => $oldValue,
                            ];

                            if ($oldValue !== $setting->typed_value) {
                                $changes[$key] = [
                                    'old' => $oldValue,
                                    'new' => $setting->typed_value,
                                ];
                            }
                        }
                    }
                }

                // Log activity
                $this->logActivity('reset_system_settings', null, null, $changes);

                DB::commit();

                return $this->successResponse([
                    'reset_settings' => $resetSettings,
                    'changes_count' => count($changes),
                ], 'System settings reset successfully');

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get system health information
     *
     * @return JsonResponse
     */
    public function health(): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('settings.view')) {
                return $this->forbiddenResponse('You do not have permission to view system health');
            }

            $health = [
                'status' => 'healthy',
                'timestamp' => now(),
                'checks' => [],
            ];

            // Database connectivity check
            try {
                DB::connection()->getPdo();
                $health['checks']['database'] = [
                    'status' => 'healthy',
                    'message' => 'Database connection successful',
                    'response_time' => $this->measureDatabaseResponseTime(),
                ];
            } catch (Exception $e) {
                $health['status'] = 'unhealthy';
                $health['checks']['database'] = [
                    'status' => 'unhealthy',
                    'message' => 'Database connection failed: ' . $e->getMessage(),
                    'response_time' => null,
                ];
            }

            // Cache connectivity check
            try {
                Cache::put('health_check', 'test', 10);
                $cacheValue = Cache::get('health_check');
                Cache::forget('health_check');
                
                $health['checks']['cache'] = [
                    'status' => $cacheValue === 'test' ? 'healthy' : 'unhealthy',
                    'message' => $cacheValue === 'test' ? 'Cache working properly' : 'Cache not working',
                ];
            } catch (Exception $e) {
                $health['status'] = 'unhealthy';
                $health['checks']['cache'] = [
                    'status' => 'unhealthy',
                    'message' => 'Cache error: ' . $e->getMessage(),
                ];
            }

            // Storage check
            try {
                $testFile = 'health_check_' . time() . '.txt';
                Storage::put($testFile, 'test');
                $fileExists = Storage::exists($testFile);
                Storage::delete($testFile);
                
                $health['checks']['storage'] = [
                    'status' => $fileExists ? 'healthy' : 'unhealthy',
                    'message' => $fileExists ? 'Storage working properly' : 'Storage not working',
                    'disk_space' => $this->getDiskSpaceInfo(),
                ];
            } catch (Exception $e) {
                $health['status'] = 'unhealthy';
                $health['checks']['storage'] = [
                    'status' => 'unhealthy',
                    'message' => 'Storage error: ' . $e->getMessage(),
                    'disk_space' => null,
                ];
            }

            // Queue check
            try {
                $queueSize = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();
                
                $health['checks']['queue'] = [
                    'status' => 'healthy',
                    'message' => 'Queue system operational',
                    'pending_jobs' => $queueSize,
                    'failed_jobs' => $failedJobs,
                ];
                
                if ($failedJobs > 10) {
                    $health['checks']['queue']['status'] = 'warning';
                    $health['checks']['queue']['message'] = 'High number of failed jobs';
                }
            } catch (Exception $e) {
                $health['checks']['queue'] = [
                    'status' => 'unknown',
                    'message' => 'Queue check failed: ' . $e->getMessage(),
                    'pending_jobs' => null,
                    'failed_jobs' => null,
                ];
            }

            $this->logActivity('viewed_system_health');

            return $this->successResponse($health, 'System health retrieved successfully');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get system performance metrics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function metrics(Request $request): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('settings.view')) {
                return $this->forbiddenResponse('You do not have permission to view system metrics');
            }

            $period = $request->get('period', '24h'); // 1h, 24h, 7d, 30d
            $endTime = now();
            
            switch ($period) {
                case '1h':
                    $startTime = $endTime->copy()->subHour();
                    break;
                case '7d':
                    $startTime = $endTime->copy()->subDays(7);
                    break;
                case '30d':
                    $startTime = $endTime->copy()->subDays(30);
                    break;
                case '24h':
                default:
                    $startTime = $endTime->copy()->subDay();
                    break;
            }

            $metrics = [
                'period' => $period,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'system' => $this->getSystemMetrics(),
                'database' => $this->getDatabaseMetrics($startTime, $endTime),
                'api' => $this->getApiMetrics($startTime, $endTime),
                'users' => $this->getUserMetrics($startTime, $endTime),
                'business' => $this->getBusinessMetrics($startTime, $endTime),
            ];

            $this->logActivity('viewed_system_metrics', null, null, ['period' => $period]);

            return $this->successResponse($metrics, 'System metrics retrieved successfully');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Clear system caches
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('settings.edit')) {
                return $this->forbiddenResponse('You do not have permission to clear system cache');
            }

            $validatedData = $this->validateRequest($request, [
                'cache_types' => 'nullable|array',
                'cache_types.*' => 'string|in:config,route,view,application,all',
            ]);

            $cacheTypes = $validatedData['cache_types'] ?? ['all'];
            $clearedCaches = [];

            foreach ($cacheTypes as $type) {
                switch ($type) {
                    case 'config':
                        Artisan::call('config:clear');
                        $clearedCaches[] = 'Configuration cache';
                        break;
                        
                    case 'route':
                        Artisan::call('route:clear');
                        $clearedCaches[] = 'Route cache';
                        break;
                        
                    case 'view':
                        Artisan::call('view:clear');
                        $clearedCaches[] = 'View cache';
                        break;
                        
                    case 'application':
                        Cache::flush();
                        $clearedCaches[] = 'Application cache';
                        break;
                        
                    case 'all':
                    default:
                        Artisan::call('config:clear');
                        Artisan::call('route:clear');
                        Artisan::call('view:clear');
                        Cache::flush();
                        $clearedCaches = ['All caches'];
                        break;
                }
            }

            // Log activity
            $this->logActivity('cleared_system_cache', null, null, ['cache_types' => $cacheTypes]);

            return $this->successResponse([
                'cleared_caches' => $clearedCaches,
                'timestamp' => now(),
            ], 'System cache cleared successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get configuration history
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function configurationHistory(Request $request): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('settings.view')) {
                return $this->forbiddenResponse('You do not have permission to view configuration history');
            }

            $query = ActivityLog::where('model_type', SystemSetting::class)
                               ->whereIn('action', ['updated', 'created', 'deleted', 'reset_system_settings', 'updated_system_settings'])
                               ->with('user')
                               ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('action')) {
                $query->where('action', $request->action);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->byDateRange($request->start_date, $request->end_date);
            }

            if ($request->filled('setting_key')) {
                $key = $request->setting_key;
                $query->where(function($q) use ($key) {
                    $q->whereJsonContains('changes', [$key => null])
                      ->orWhereJsonContains('changes->' . $key, null);
                });
            }

            $perPage = min($request->get('per_page', 20), 100);
            $history = $query->paginate($perPage);

            // Transform data
            $history->getCollection()->transform(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'action_description' => $log->action_description,
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'email' => $log->user->email,
                    ] : null,
                    'changes' => $log->changes,
                    'changes_summary' => $log->changes_summary,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at,
                ];
            });

            return $this->paginatedResponse($history, 'Configuration history retrieved successfully');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Initialize default system settings
     *
     * @return JsonResponse
     */
    public function initializeDefaults(): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('settings.edit')) {
                return $this->forbiddenResponse('You do not have permission to initialize system settings');
            }

            SystemSetting::initializeDefaults();

            // Log activity
            $this->logActivity('initialized_default_settings');

            return $this->successResponse([
                'initialized_at' => now(),
                'settings_count' => count(SystemSetting::getDefaultSettings()),
            ], 'Default system settings initialized successfully');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Private helper methods
     */
    private function measureDatabaseResponseTime(): float
    {
        $start = microtime(true);
        DB::select('SELECT 1');
        return round((microtime(true) - $start) * 1000, 2); // milliseconds
    }

    private function getDiskSpaceInfo(): array
    {
        $path = storage_path();
        $totalBytes = disk_total_space($path);
        $freeBytes = disk_free_space($path);
        $usedBytes = $totalBytes - $freeBytes;

        return [
            'total' => $this->formatBytes($totalBytes),
            'used' => $this->formatBytes($usedBytes),
            'free' => $this->formatBytes($freeBytes),
            'usage_percentage' => round(($usedBytes / $totalBytes) * 100, 2),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function getSystemMetrics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage' => [
                'current' => $this->formatBytes(memory_get_usage(true)),
                'peak' => $this->formatBytes(memory_get_peak_usage(true)),
                'limit' => ini_get('memory_limit'),
            ],
            'server_load' => sys_getloadavg(),
            'uptime' => $this->getSystemUptime(),
        ];
    }

    private function getDatabaseMetrics($startTime, $endTime): array
    {
        try {
            return [
                'connection_count' => DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0,
                'query_count' => DB::select("SHOW STATUS LIKE 'Questions'")[0]->Value ?? 0,
                'slow_queries' => DB::select("SHOW STATUS LIKE 'Slow_queries'")[0]->Value ?? 0,
                'table_count' => count(DB::select("SHOW TABLES")),
                'database_size' => $this->getDatabaseSize(),
            ];
        } catch (Exception $e) {
            return [
                'error' => 'Unable to retrieve database metrics: ' . $e->getMessage(),
            ];
        }
    }

    private function getApiMetrics($startTime, $endTime): array
    {
        $totalRequests = ActivityLog::whereBetween('created_at', [$startTime, $endTime])->count();
        $errorRequests = ActivityLog::whereBetween('created_at', [$startTime, $endTime])
                                   ->where('action', 'like', '%error%')
                                   ->count();

        return [
            'total_requests' => $totalRequests,
            'error_requests' => $errorRequests,
            'success_rate' => $totalRequests > 0 ? round((($totalRequests - $errorRequests) / $totalRequests) * 100, 2) : 100,
            'average_response_time' => $this->measureDatabaseResponseTime(), // Simplified metric
        ];
    }

    private function getUserMetrics($startTime, $endTime): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::active()->count(),
            'online_users' => User::where('last_login_at', '>=', now()->subMinutes(15))->count(),
            'new_users' => User::whereBetween('created_at', [$startTime, $endTime])->count(),
            'user_activity' => ActivityLog::whereBetween('created_at', [$startTime, $endTime])
                                         ->whereNotNull('user_id')
                                         ->count(),
        ];
    }

    private function getBusinessMetrics($startTime, $endTime): array
    {
        // These would be implemented based on your business models
        return [
            'new_bookings' => 0, // Booking::whereBetween('created_at', [$startTime, $endTime])->count(),
            'new_quotes' => 0,   // Quote::whereBetween('created_at', [$startTime, $endTime])->count(),
            'revenue' => 0,      // Payment::whereBetween('created_at', [$startTime, $endTime])->sum('amount'),
        ];
    }

    private function getSystemUptime(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = file_get_contents('/proc/uptime');
            $uptimeSeconds = (int) explode(' ', $uptime)[0];
            return $this->formatUptime($uptimeSeconds);
        }
        
        return 'Unknown';
    }

    private function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return "{$days}d {$hours}h {$minutes}m";
    }

    private function getDatabaseSize(): string
    {
        try {
            $result = DB::select("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            
            return ($result[0]->size_mb ?? 0) . ' MB';
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
}