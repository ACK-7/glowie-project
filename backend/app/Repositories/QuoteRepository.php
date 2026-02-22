<?php

namespace App\Repositories;

use App\Models\Quote;
use App\Repositories\Contracts\QuoteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Quote Repository Implementation
 */
class QuoteRepository extends BaseRepository implements QuoteRepositoryInterface
{
    public function __construct(Quote $model)
    {
        parent::__construct($model);
    }

    /**
     * Get quotes by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->byStatus($status)->get();
    }

    /**
     * Get pending quotes
     */
    public function getPending(): Collection
    {
        return $this->model->pending()->get();
    }

    /**
     * Get approved quotes
     */
    public function getApproved(): Collection
    {
        return $this->model->approved()->get();
    }

    /**
     * Get expired quotes
     */
    public function getExpired(): Collection
    {
        return $this->model->expired()->get();
    }

    /**
     * Get valid quotes
     */
    public function getValid(): Collection
    {
        return $this->model->valid()->get();
    }

    /**
     * Get filtered and paginated quotes with relationships
     */
    public function getFilteredPaginatedWithRelations(array $filters = [], int $perPage = 15)
    {
        $query = $this->model->newQuery();
        
        // Load relationships
        $query->with(['customer', 'route', 'createdBy', 'approvedBy']);
        
        // Apply filters
        $query = $this->applyFilters($query, $filters);
        
        // Default sorting: latest quotes first
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);
        
        return $query->paginate($perPage);
    }

    /**
     * Get quotes by customer with relationships
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->model->byCustomer($customerId)
                          ->with(['customer', 'route'])
                          ->get();
    }

    /**
     * Get quotes by route
     */
    public function getByRoute(int $routeId): Collection
    {
        return $this->model->byRoute($routeId)->get();
    }

    /**
     * Get quotes expiring soon
     */
    public function getExpiringSoon(int $days = 7): Collection
    {
        return $this->model
            ->where('valid_until', '<=', now()->addDays($days))
            ->where('valid_until', '>', now())
            ->whereIn('status', [Quote::STATUS_PENDING, Quote::STATUS_APPROVED])
            ->with(['customer', 'route'])
            ->get();
    }

    /**
     * Get quote conversion statistics
     */
    public function getConversionStatistics(): array
    {
        $totalQuotes = $this->model->count();
        $convertedQuotes = $this->model->where('status', Quote::STATUS_CONVERTED)->count();
        $approvedQuotes = $this->model->where('status', Quote::STATUS_APPROVED)->count();
        $rejectedQuotes = $this->model->where('status', Quote::STATUS_REJECTED)->count();
        $expiredQuotes = $this->model->where('status', Quote::STATUS_EXPIRED)->count();

        return [
            'total_quotes' => $totalQuotes,
            'converted_quotes' => $convertedQuotes,
            'approved_quotes' => $approvedQuotes,
            'rejected_quotes' => $rejectedQuotes,
            'expired_quotes' => $expiredQuotes,
            'conversion_rate' => $totalQuotes > 0 ? ($convertedQuotes / $totalQuotes) * 100 : 0,
            'approval_rate' => $totalQuotes > 0 ? (($approvedQuotes + $convertedQuotes) / $totalQuotes) * 100 : 0,
            'rejection_rate' => $totalQuotes > 0 ? ($rejectedQuotes / $totalQuotes) * 100 : 0,
            'expiry_rate' => $totalQuotes > 0 ? ($expiredQuotes / $totalQuotes) * 100 : 0,
        ];
    }

    /**
     * Get quote trends
     */
    public function getQuoteTrends(int $days = 30): Collection
    {
        return $this->model
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as quotes_count'),
                DB::raw('SUM(total_amount) as total_value'),
                DB::raw('AVG(total_amount) as average_value'),
                'status'
            ])
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(['date', 'status'])
            ->orderBy('date')
            ->get();
    }

    /**
     * Search quotes
     */
    public function searchQuotes(string $query): Collection
    {
        return $this->model
            ->with(['customer', 'route'])
            ->where(function ($q) use ($query) {
                $q->where('quote_reference', 'LIKE', "%{$query}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($query) {
                      $customerQuery->where('first_name', 'LIKE', "%{$query}%")
                                   ->orWhere('last_name', 'LIKE', "%{$query}%")
                                   ->orWhere('email', 'LIKE', "%{$query}%");
                  })
                  ->orWhereRaw("JSON_EXTRACT(vehicle_details, '$.make') LIKE ?", ["%{$query}%"])
                  ->orWhereRaw("JSON_EXTRACT(vehicle_details, '$.model') LIKE ?", ["%{$query}%"]);
            })
            ->get();
    }

    /**
     * Get recent quotes
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model
            ->with(['customer', 'route'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get quotes requiring approval
     */
    public function getRequiringApproval(): Collection
    {
        return $this->model
            ->with(['customer', 'route'])
            ->where('status', Quote::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get quote analytics by date range
     */
    public function getAnalyticsByDateRange(string $startDate, string $endDate): array
    {
        $quotes = $this->model
            ->with(['customer', 'route'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'total_quotes' => $quotes->count(),
            'total_value' => $quotes->sum('total_amount'),
            'average_value' => $quotes->avg('total_amount'),
            'status_breakdown' => $quotes->groupBy('status')->map->count(),
            'route_breakdown' => $quotes->groupBy('route.full_route')->map(function ($routeQuotes) {
                return [
                    'count' => $routeQuotes->count(),
                    'total_value' => $routeQuotes->sum('total_amount'),
                    'average_value' => $routeQuotes->avg('total_amount'),
                ];
            }),
            'monthly_trends' => $quotes->groupBy(function ($quote) {
                return $quote->created_at->format('Y-m');
            })->map(function ($monthQuotes) {
                return [
                    'count' => $monthQuotes->count(),
                    'value' => $monthQuotes->sum('total_amount'),
                    'conversions' => $monthQuotes->where('status', Quote::STATUS_CONVERTED)->count(),
                ];
            }),
            'conversion_funnel' => [
                'created' => $quotes->count(),
                'approved' => $quotes->where('status', Quote::STATUS_APPROVED)->count(),
                'converted' => $quotes->where('status', Quote::STATUS_CONVERTED)->count(),
                'rejected' => $quotes->where('status', Quote::STATUS_REJECTED)->count(),
                'expired' => $quotes->where('status', Quote::STATUS_EXPIRED)->count(),
            ],
        ];
    }

    /**
     * Get average quote value by route
     */
    public function getAverageValueByRoute(): Collection
    {
        return $this->model
            ->join('routes', 'quotes.route_id', '=', 'routes.id')
            ->select([
                'routes.id',
                'routes.origin_country',
                'routes.destination_country',
                DB::raw('COUNT(quotes.id) as quote_count'),
                DB::raw('AVG(quotes.total_amount) as average_value'),
                DB::raw('MIN(quotes.total_amount) as min_value'),
                DB::raw('MAX(quotes.total_amount) as max_value'),
                DB::raw('SUM(quotes.total_amount) as total_value')
            ])
            ->groupBy(['routes.id', 'routes.origin_country', 'routes.destination_country'])
            ->orderBy('quote_count', 'desc')
            ->get();
    }

    /**
     * Apply search filter for quotes
     */
    protected function applySearchFilter(Builder $query, string $searchTerm): void
    {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('quote_reference', 'LIKE', "%{$searchTerm}%")
              ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                  $customerQuery->where('first_name', 'LIKE', "%{$searchTerm}%")
                               ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                               ->orWhere('email', 'LIKE', "%{$searchTerm}%");
              })
              ->orWhereRaw("JSON_EXTRACT(vehicle_details, '$.make') LIKE ?", ["%{$searchTerm}%"])
              ->orWhereRaw("JSON_EXTRACT(vehicle_details, '$.model') LIKE ?", ["%{$searchTerm}%"]);
        });
    }

    /**
     * Apply custom filters for quotes
     */
    protected function applyCustomFilter(Builder $query, string $key, $value): void
    {
        switch ($key) {
            case 'customer_id':
                $query->where('customer_id', $value);
                break;
                
            case 'route_id':
                $query->where('route_id', $value);
                break;
                
            case 'expiring_soon':
                if ($value) {
                    $query->where('valid_until', '<=', now()->addDays(7))
                          ->where('valid_until', '>', now())
                          ->whereIn('status', [Quote::STATUS_PENDING, Quote::STATUS_APPROVED]);
                }
                break;
                
            case 'amount_min':
                $query->where('total_amount', '>=', $value);
                break;
                
            case 'amount_max':
                $query->where('total_amount', '<=', $value);
                break;
                
            case 'vehicle_make':
                $query->whereRaw("JSON_EXTRACT(vehicle_details, '$.make') = ?", [$value]);
                break;
                
            case 'vehicle_year':
                $query->whereRaw("JSON_EXTRACT(vehicle_details, '$.year') = ?", [$value]);
                break;
        }
    }
}