<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Booking Repository Implementation
 */
class BookingRepository extends BaseRepository implements BookingRepositoryInterface
{
    public function __construct(Booking $model)
    {
        parent::__construct($model);
    }

    /**
     * Get bookings by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->byStatus($status)->get();
    }

    /**
     * Get active bookings (not cancelled or delivered)
     */
    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * Get completed bookings
     */
    public function getCompleted(): Collection
    {
        return $this->model->completed()->get();
    }

    /**
     * Get bookings in progress
     */
    public function getInProgress(): Collection
    {
        return $this->model->inProgress()->get();
    }

    /**
     * Get filtered and paginated bookings with relationships
     */
    public function getFilteredPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        
        // Always load relationships
        $query->with(['customer', 'quote', 'route', 'vehicle']);
        
        // Apply filters
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            switch ($key) {
                case 'status':
                    $query->where('status', $value);
                    break;
                    
                case 'search':
                    $query->where(function ($q) use ($value) {
                        $q->where('booking_reference', 'like', "%{$value}%")
                          ->orWhere('recipient_name', 'like', "%{$value}%")
                          ->orWhere('recipient_email', 'like', "%{$value}%")
                          ->orWhereHas('customer', function ($customerQuery) use ($value) {
                              $customerQuery->where('first_name', 'like', "%{$value}%")
                                          ->orWhere('last_name', 'like', "%{$value}%")
                                          ->orWhere('email', 'like', "%{$value}%");
                          })
                          ->orWhereHas('vehicle', function ($vehicleQuery) use ($value) {
                              $vehicleQuery->where('make', 'like', "%{$value}%")
                                         ->orWhere('model', 'like', "%{$value}%");
                          });
                    });
                    break;
                    
                case 'customer_id':
                    $query->where('customer_id', $value);
                    break;
                    
                case 'route_id':
                    $query->where('route_id', $value);
                    break;
                    
                case 'payment_status':
                    // Calculate payment status on the fly
                    if ($value === 'paid') {
                        $query->whereRaw('paid_amount >= total_amount AND total_amount > 0');
                    } elseif ($value === 'partial') {
                        $query->whereRaw('paid_amount > 0 AND paid_amount < total_amount');
                    } elseif ($value === 'unpaid') {
                        $query->where('paid_amount', 0);
                    }
                    break;
                    
                case 'start_date':
                    $query->where('created_at', '>=', $value);
                    break;
                    
                case 'end_date':
                    $query->where('created_at', '<=', $value);
                    break;
                    
                case 'overdue':
                    if ($value) {
                        $query->where('estimated_delivery', '<', now())
                              ->whereNotIn('status', ['delivered', 'cancelled']);
                    }
                    break;
                    
                case 'amount_min':
                    $query->where('total_amount', '>=', $value);
                    break;
                    
                case 'amount_max':
                    $query->where('total_amount', '<=', $value);
                    break;
                    
                case 'sort_by':
                    $sortDirection = $filters['sort_direction'] ?? 'desc';
                    $query->orderBy($value, $sortDirection);
                    break;
            }
        }
        
        // Default sorting
        if (!isset($filters['sort_by'])) {
            $query->orderBy('created_at', 'desc');
        }
        
        return $query->paginate($perPage);
    }

    /**
     * Get bookings by customer
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->model->byCustomer($customerId)
            ->with(['vehicle', 'route', 'quote', 'shipment'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get bookings by route
     */
    public function getByRoute(int $routeId): Collection
    {
        return $this->model->byRoute($routeId)->get();
    }

    /**
     * Get overdue bookings
     */
    public function getOverdue(): Collection
    {
        return $this->model->overdue()->get();
    }

    /**
     * Update booking status
     */
    public function updateStatus(int $id, string $status): bool
    {
        $booking = $this->findOrFail($id);
        return $booking->updateStatus($status);
    }

    /**
     * Get bookings with full relationships
     */
    public function getWithFullRelations(): Collection
    {
        return $this->model->with([
            'customer',
            'quote',
            'vehicle',
            'route',
            'shipment',
            'documents',
            'payments',
            'createdBy',
            'updatedBy'
        ])->get();
    }

    /**
     * Get booking statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $query = $this->model->newQuery();
        $query = $this->applyFilters($query, $filters);

        return [
            'total_bookings' => $query->count(),
            'pending_bookings' => (clone $query)->where('status', Booking::STATUS_PENDING)->count(),
            'confirmed_bookings' => (clone $query)->where('status', Booking::STATUS_CONFIRMED)->count(),
            'in_transit_bookings' => (clone $query)->where('status', Booking::STATUS_IN_TRANSIT)->count(),
            'delivered_bookings' => (clone $query)->where('status', Booking::STATUS_DELIVERED)->count(),
            'cancelled_bookings' => (clone $query)->where('status', Booking::STATUS_CANCELLED)->count(),
            'total_revenue' => (clone $query)->sum('total_amount'),
            'paid_amount' => (clone $query)->sum('paid_amount'),
            'outstanding_amount' => (clone $query)->sum(DB::raw('total_amount - paid_amount')),
            'average_booking_value' => (clone $query)->avg('total_amount'),
        ];
    }

    /**
     * Get revenue by period
     */
    public function getRevenueByPeriod(string $period = 'month'): Collection
    {
        $dateFormat = match ($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m'
        };

        return $this->model
            ->select([
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('SUM(paid_amount) as paid_revenue'),
                DB::raw('COUNT(*) as booking_count')
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    /**
     * Get booking trends
     */
    public function getBookingTrends(int $days = 30): Collection
    {
        return $this->model
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as bookings_count'),
                DB::raw('SUM(total_amount) as revenue'),
                'status'
            ])
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(['date', 'status'])
            ->orderBy('date')
            ->get();
    }

    /**
     * Get bookings by payment status
     */
    public function getByPaymentStatus(string $paymentStatus): Collection
    {
        return $this->model
            ->whereRaw("
                CASE 
                    WHEN paid_amount >= total_amount THEN 'paid'
                    WHEN paid_amount > 0 THEN 'partial'
                    ELSE 'unpaid'
                END = ?
            ", [$paymentStatus])
            ->get();
    }

    /**
     * Search bookings by reference or customer
     */
    public function searchBookings(string $query): Collection
    {
        return $this->model
            ->with(['customer', 'vehicle', 'route'])
            ->where(function ($q) use ($query) {
                $q->where('booking_reference', 'LIKE', "%{$query}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($query) {
                      $customerQuery->where('first_name', 'LIKE', "%{$query}%")
                                   ->orWhere('last_name', 'LIKE', "%{$query}%")
                                   ->orWhere('email', 'LIKE', "%{$query}%")
                                   ->orWhere('phone', 'LIKE', "%{$query}%");
                  })
                  ->orWhereHas('vehicle', function ($vehicleQuery) use ($query) {
                      $vehicleQuery->where('make', 'LIKE', "%{$query}%")
                                  ->orWhere('model', 'LIKE', "%{$query}%")
                                  ->orWhere('vin', 'LIKE', "%{$query}%");
                  });
            })
            ->get();
    }

    /**
     * Get recent bookings
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model
            ->with(['customer', 'vehicle', 'route'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get bookings requiring attention
     */
    public function getRequiringAttention(): Collection
    {
        return $this->model
            ->with(['customer', 'shipment', 'documents', 'payments'])
            ->where(function ($query) {
                $query->where('status', Booking::STATUS_PENDING)
                      ->orWhere(function ($q) {
                          $q->where('estimated_delivery', '<', now())
                            ->whereNotIn('status', [Booking::STATUS_DELIVERED, Booking::STATUS_CANCELLED]);
                      })
                      ->orWhereHas('payments', function ($paymentQuery) {
                          $paymentQuery->where('status', 'pending')
                                      ->where('created_at', '<', now()->subDays(7));
                      })
                      ->orWhereHas('documents', function ($docQuery) {
                          $docQuery->where('status', 'pending')
                                  ->orWhere('expiry_date', '<', now()->addDays(30));
                      });
            })
            ->get();
    }

    /**
     * Get bookings by date range with analytics
     */
    public function getAnalyticsByDateRange(string $startDate, string $endDate): array
    {
        $bookings = $this->model
            ->with(['customer', 'route', 'payments'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $analytics = [
            'total_bookings' => $bookings->count(),
            'total_revenue' => $bookings->sum('total_amount'),
            'paid_revenue' => $bookings->sum('paid_amount'),
            'average_booking_value' => $bookings->avg('total_amount'),
            'status_breakdown' => $bookings->groupBy('status')->map->count(),
            'route_breakdown' => $bookings->groupBy('route.full_route')->map->count(),
            'customer_segments' => $bookings->groupBy('customer.customer_tier')->map->count(),
            'payment_status_breakdown' => $bookings->groupBy('payment_status')->map->count(),
            'monthly_trends' => $bookings->groupBy(function ($booking) {
                return $booking->created_at->format('Y-m');
            })->map(function ($monthBookings) {
                return [
                    'count' => $monthBookings->count(),
                    'revenue' => $monthBookings->sum('total_amount'),
                ];
            }),
        ];

        return $analytics;
    }

    /**
     * Apply search filter for bookings
     */
    protected function applySearchFilter(Builder $query, string $searchTerm): void
    {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('booking_reference', 'LIKE', "%{$searchTerm}%")
              ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                  $customerQuery->where('first_name', 'LIKE', "%{$searchTerm}%")
                               ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                               ->orWhere('email', 'LIKE', "%{$searchTerm}%");
              });
        });
    }

    /**
     * Apply custom filters for bookings
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
                
            case 'payment_status':
                $this->applyPaymentStatusFilter($query, $value);
                break;
                
            case 'overdue':
                if ($value) {
                    $query->where('estimated_delivery', '<', now())
                          ->whereNotIn('status', [Booking::STATUS_DELIVERED, Booking::STATUS_CANCELLED]);
                }
                break;
                
            case 'amount_min':
                $query->where('total_amount', '>=', $value);
                break;
                
            case 'amount_max':
                $query->where('total_amount', '<=', $value);
                break;
        }
    }

    /**
     * Apply payment status filter
     */
    private function applyPaymentStatusFilter(Builder $query, string $paymentStatus): void
    {
        switch ($paymentStatus) {
            case 'paid':
                $query->whereRaw('paid_amount >= total_amount');
                break;
                
            case 'partial':
                $query->whereRaw('paid_amount > 0 AND paid_amount < total_amount');
                break;
                
            case 'unpaid':
                $query->where('paid_amount', 0);
                break;
        }
    }
}