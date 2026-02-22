<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\Quote;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\Document;

/**
 * Performance Optimization Service
 * 
 * Handles caching strategies, query optimization, and performance monitoring
 * to ensure the application runs efficiently at scale
 */
class PerformanceOptimizationService
{
    private const CACHE_TTL_SHORT = 300; // 5 minutes
    private const CACHE_TTL_MEDIUM = 1800; // 30 minutes
    private const CACHE_TTL_LONG = 3600; // 1 hour
    private const CACHE_TTL_DAILY = 86400; // 24 hours

    /**
     * Get cached dashboard statistics with intelligent cache invalidation
     */
    public function getCachedDashboardStats(): array
    {
        return Cache::remember('dashboard.stats.optimized', self::CACHE_TTL_MEDIUM, function () {
            return $this->calculateOptimizedDashboardStats();
        });
    }

    /**
     * Get cached customer data with relationship preloading
     */
    public function getCachedCustomerData(int $customerId): ?array
    {
        $cacheKey = "customer.data.{$customerId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL_MEDIUM, function () use ($customerId) {
            $customer = Customer::with([
                'bookings' => function ($query) {
                    $query->select('id', 'customer_id', 'status', 'total_amount', 'created_at')
                          ->orderBy('created_at', 'desc')
                          ->limit(10);
                },
                'quotes' => function ($query) {
                    $query->select('id', 'customer_id', 'status', 'total_amount', 'created_at')
                          ->orderBy('created_at', 'desc')
                          ->limit(5);
                },
                'payments' => function ($query) {
                    $query->select('id', 'customer_id', 'amount', 'status', 'payment_date')
                          ->where('status', 'completed')
                          ->orderBy('payment_date', 'desc')
                          ->limit(5);
                }
            ])->find($customerId);

            if (!$customer) {
                return null;
            }

            return [
                'customer' => $customer->toArray(),
                'summary' => [
                    'total_bookings' => $customer->bookings->count(),
                    'total_spent' => $customer->payments->sum('amount'),
                    'last_booking' => $customer->bookings->first()?->created_at,
                    'customer_since' => $customer->created_at,
                    'tier' => $this->calculateCustomerTier($customer),
                ],
                'cached_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get cached booking data with optimized queries
     */
    public function getCachedBookingData(int $bookingId): ?array
    {
        $cacheKey = "booking.data.{$bookingId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($bookingId) {
            $booking = Booking::with([
                'customer:id,first_name,last_name,email,phone',
                'quote:id,quote_reference,total_amount',
                'vehicle:id,make,model,year,color',
                'route:id,origin_country,destination_country,base_price',
                'shipment:id,tracking_number,status,current_location,estimated_arrival',
                'payments:id,amount,status,payment_method,payment_date',
                'documents:id,document_type,status,file_name,verified_at'
            ])->find($bookingId);

            if (!$booking) {
                return null;
            }

            return [
                'booking' => $booking->toArray(),
                'progress' => $this->calculateBookingProgress($booking),
                'next_actions' => $this->getBookingNextActions($booking),
                'cached_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Optimize database queries with intelligent indexing suggestions
     */
    public function optimizeQueries(): array
    {
        $optimizations = [];

        // Analyze slow queries
        $slowQueries = $this->analyzeSlowQueries();
        $optimizations['slow_queries'] = $slowQueries;

        // Check missing indexes
        $missingIndexes = $this->checkMissingIndexes();
        $optimizations['missing_indexes'] = $missingIndexes;

        // Analyze query patterns
        $queryPatterns = $this->analyzeQueryPatterns();
        $optimizations['query_patterns'] = $queryPatterns;

        // Cache optimization suggestions
        Cache::put('performance.optimizations', $optimizations, self::CACHE_TTL_LONG);

        return $optimizations;
    }

    /**
     * Implement intelligent cache warming for frequently accessed data
     */
    public function warmCache(): array
    {
        $warmedCaches = [];

        try {
            // Warm dashboard statistics
            $this->getCachedDashboardStats();
            $warmedCaches[] = 'dashboard_stats';

            // Warm recent customers data
            $recentCustomers = Customer::orderBy('last_login_at', 'desc')->limit(50)->pluck('id');
            foreach ($recentCustomers as $customerId) {
                $this->getCachedCustomerData($customerId);
            }
            $warmedCaches[] = "customer_data_{$recentCustomers->count()}_records";

            // Warm active bookings data
            $activeBookings = Booking::whereIn('status', ['confirmed', 'in_transit'])->limit(100)->pluck('id');
            foreach ($activeBookings as $bookingId) {
                $this->getCachedBookingData($bookingId);
            }
            $warmedCaches[] = "booking_data_{$activeBookings->count()}_records";

            // Warm frequently accessed routes
            $this->cachePopularRoutes();
            $warmedCaches[] = 'popular_routes';

            // Warm payment statistics
            $this->cachePaymentStatistics();
            $warmedCaches[] = 'payment_statistics';

            Log::info('Cache warming completed', [
                'warmed_caches' => $warmedCaches,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Cache warming failed', [
                'error' => $e->getMessage(),
                'warmed_caches' => $warmedCaches,
            ]);
        }

        return $warmedCaches;
    }

    /**
     * Clear specific cache patterns
     */
    public function clearCachePattern(string $pattern): int
    {
        try {
            if (config('cache.default') === 'redis') {
                $keys = Redis::keys("*{$pattern}*");
                if (!empty($keys)) {
                    return Redis::del($keys);
                }
            } else {
                // For file-based cache, we'll use a different approach
                Cache::flush(); // This is less efficient but works for all cache drivers
                return 1;
            }
        } catch (\Exception $e) {
            Log::error('Failed to clear cache pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }

        return 0;
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return Cache::remember('performance.metrics', self::CACHE_TTL_SHORT, function () {
            return [
                'database' => $this->getDatabaseMetrics(),
                'cache' => $this->getCacheMetrics(),
                'memory' => $this->getMemoryMetrics(),
                'queries' => $this->getQueryMetrics(),
                'response_times' => $this->getResponseTimeMetrics(),
                'generated_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Invalidate related caches when data changes
     */
    public function invalidateRelatedCaches(string $model, int $id, array $relations = []): void
    {
        $cacheKeys = [];

        switch ($model) {
            case 'Customer':
                $cacheKeys[] = "customer.data.{$id}";
                $cacheKeys[] = 'dashboard.stats.optimized';
                break;

            case 'Booking':
                $cacheKeys[] = "booking.data.{$id}";
                $cacheKeys[] = 'dashboard.stats.optimized';
                // Also invalidate customer cache if customer_id is in relations
                if (isset($relations['customer_id'])) {
                    $cacheKeys[] = "customer.data.{$relations['customer_id']}";
                }
                break;

            case 'Quote':
                $cacheKeys[] = 'dashboard.stats.optimized';
                if (isset($relations['customer_id'])) {
                    $cacheKeys[] = "customer.data.{$relations['customer_id']}";
                }
                break;

            case 'Payment':
                $cacheKeys[] = 'dashboard.stats.optimized';
                $cacheKeys[] = 'payment.statistics';
                if (isset($relations['customer_id'])) {
                    $cacheKeys[] = "customer.data.{$relations['customer_id']}";
                }
                if (isset($relations['booking_id'])) {
                    $cacheKeys[] = "booking.data.{$relations['booking_id']}";
                }
                break;
        }

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        Log::info('Cache invalidation completed', [
            'model' => $model,
            'id' => $id,
            'invalidated_keys' => $cacheKeys,
        ]);
    }

    // Private helper methods

    private function calculateOptimizedDashboardStats(): array
    {
        // Use raw queries for better performance
        $stats = DB::select("
            SELECT 
                'quotes' as type,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
            FROM quotes
            UNION ALL
            SELECT 
                'bookings' as type,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
            FROM bookings
            UNION ALL
            SELECT 
                'customers' as type,
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
            FROM customers
        ");

        $organized = [];
        foreach ($stats as $stat) {
            $organized[$stat->type] = [
                'total' => $stat->total,
                'pending' => $stat->pending ?? 0,
                'approved' => $stat->approved ?? 0,
                'confirmed' => $stat->confirmed ?? 0,
                'active' => $stat->active ?? 0,
                'verified' => $stat->verified ?? 0,
                'today' => $stat->today,
            ];
        }

        // Add revenue data
        $revenue = DB::selectOne("
            SELECT 
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total,
                SUM(CASE WHEN status = 'completed' AND DATE(payment_date) = CURDATE() THEN amount ELSE 0 END) as today
            FROM payments
        ");

        $organized['revenue'] = [
            'total' => $revenue->total ?? 0,
            'today' => $revenue->today ?? 0,
        ];

        $organized['last_updated'] = now()->toISOString();

        return $organized;
    }

    private function calculateCustomerTier(Customer $customer): string
    {
        $totalSpent = $customer->payments->sum('amount');
        $bookingCount = $customer->bookings->count();

        if ($totalSpent >= 10000 || $bookingCount >= 10) {
            return 'platinum';
        } elseif ($totalSpent >= 5000 || $bookingCount >= 5) {
            return 'gold';
        } elseif ($totalSpent >= 1000 || $bookingCount >= 2) {
            return 'silver';
        }

        return 'bronze';
    }

    private function calculateBookingProgress(Booking $booking): array
    {
        $steps = ['pending', 'confirmed', 'in_transit', 'delivered'];
        $currentIndex = array_search($booking->status, $steps);
        
        return [
            'current_step' => $booking->status,
            'progress_percentage' => $currentIndex !== false ? (($currentIndex + 1) / count($steps)) * 100 : 0,
            'completed_steps' => $currentIndex !== false ? array_slice($steps, 0, $currentIndex + 1) : [],
            'remaining_steps' => $currentIndex !== false ? array_slice($steps, $currentIndex + 1) : $steps,
        ];
    }

    private function getBookingNextActions(Booking $booking): array
    {
        $actions = [];

        switch ($booking->status) {
            case 'pending':
                $actions[] = 'Confirm booking payment';
                $actions[] = 'Upload required documents';
                break;
            case 'confirmed':
                $actions[] = 'Prepare shipment';
                $actions[] = 'Verify all documents';
                break;
            case 'in_transit':
                $actions[] = 'Track shipment progress';
                $actions[] = 'Prepare for customs clearance';
                break;
            case 'delivered':
                $actions[] = 'Collect customer feedback';
                break;
        }

        return $actions;
    }

    private function analyzeSlowQueries(): array
    {
        // This would typically analyze the slow query log
        // For now, return a placeholder structure
        return [
            'total_slow_queries' => 0,
            'average_execution_time' => 0,
            'most_frequent_slow_queries' => [],
        ];
    }

    private function checkMissingIndexes(): array
    {
        // Analyze common query patterns and suggest indexes
        return [
            'suggested_indexes' => [
                'bookings' => ['customer_id', 'status', 'created_at'],
                'quotes' => ['customer_id', 'status', 'valid_until'],
                'payments' => ['booking_id', 'status', 'payment_date'],
                'documents' => ['booking_id', 'status', 'expiry_date'],
            ],
        ];
    }

    private function analyzeQueryPatterns(): array
    {
        return [
            'most_frequent_tables' => ['bookings', 'customers', 'quotes', 'payments'],
            'most_frequent_joins' => ['bookings-customers', 'bookings-quotes', 'bookings-payments'],
            'optimization_opportunities' => [
                'Add composite indexes for frequently filtered columns',
                'Consider query result caching for dashboard statistics',
                'Optimize N+1 query patterns with eager loading',
            ],
        ];
    }

    private function cachePopularRoutes(): void
    {
        $routes = DB::table('routes')
            ->join('bookings', 'routes.id', '=', 'bookings.route_id')
            ->select('routes.*', DB::raw('COUNT(bookings.id) as booking_count'))
            ->groupBy('routes.id')
            ->orderBy('booking_count', 'desc')
            ->limit(10)
            ->get();

        Cache::put('popular.routes', $routes, self::CACHE_TTL_DAILY);
    }

    private function cachePaymentStatistics(): void
    {
        $stats = DB::table('payments')
            ->select(
                DB::raw('payment_method'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('AVG(amount) as average_amount')
            )
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->get();

        Cache::put('payment.statistics', $stats, self::CACHE_TTL_LONG);
    }

    private function getDatabaseMetrics(): array
    {
        return [
            'total_queries' => 0, // Would be tracked by query logging
            'slow_queries' => 0,
            'average_query_time' => 0,
            'connection_count' => 1,
        ];
    }

    private function getCacheMetrics(): array
    {
        return [
            'hit_rate' => 85.5, // Placeholder
            'miss_rate' => 14.5,
            'total_keys' => Cache::get('cache.key.count', 0),
            'memory_usage' => '50MB', // Placeholder
        ];
    }

    private function getMemoryMetrics(): array
    {
        return [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
        ];
    }

    private function getQueryMetrics(): array
    {
        return [
            'total_queries' => DB::getQueryLog() ? count(DB::getQueryLog()) : 0,
            'unique_queries' => 0,
            'most_frequent' => [],
        ];
    }

    private function getResponseTimeMetrics(): array
    {
        return [
            'average_response_time' => 150, // ms, placeholder
            'p95_response_time' => 300,
            'p99_response_time' => 500,
        ];
    }
}