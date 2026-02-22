<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Customer Repository Implementation
 */
class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    /**
     * Override applyFilters to handle status filter properly
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        // Handle status filter - now we have a real status column
        if (isset($filters['status']) && in_array($filters['status'], ['active', 'inactive', 'suspended'])) {
            $query->where('status', $filters['status']);
            // Remove status from filters so base class doesn't process it again
            unset($filters['status']);
        }
        
        // Call parent implementation for other filters
        return parent::applyFilters($query, $filters);
    }

    /**
     * Get active customers
     */
    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * Get verified customers
     */
    public function getVerified(): Collection
    {
        return $this->model->verified()->get();
    }

    /**
     * Get customers by tier
     */
    public function getByTier(string $tier): Collection
    {
        return $this->model->get()->filter(function ($customer) use ($tier) {
            return $customer->getCustomerTier() === $tier;
        });
    }

    /**
     * Find customer by email
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Search customers by multiple fields
     */
    public function searchCustomers(string $query, array $fields = ['name', 'email', 'phone']): Collection
    {
        return $this->model
            ->where(function ($q) use ($query, $fields) {
                if (in_array('name', $fields)) {
                    $q->where('first_name', 'LIKE', "%{$query}%")
                      ->orWhere('last_name', 'LIKE', "%{$query}%")
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
                }
                if (in_array('email', $fields)) {
                    $q->orWhere('email', 'LIKE', "%{$query}%");
                }
                if (in_array('phone', $fields)) {
                    $q->orWhere('phone', 'LIKE', "%{$query}%");
                }
                if (in_array('id_number', $fields)) {
                    $q->orWhere('id_number', 'LIKE', "%{$query}%");
                }
            })
            ->get();
    }

    /**
     * Get customers by tier
     */
    public function getCustomersByTier(string $tier): Collection
    {
        return $this->model->get()->filter(function ($customer) use ($tier) {
            return $customer->getCustomerTier() === $tier;
        });
    }

    /**
     * Get customers requiring attention
     */
    public function getCustomersRequiringAttention(): Collection
    {
        return $this->model
            ->where(function ($query) {
                $query->where('is_active', false)
                      ->orWhere('is_verified', false)
                      ->orWhereHas('bookings', function ($bookingQuery) {
                          $bookingQuery->where('status', 'pending')
                                      ->where('created_at', '<', now()->subDays(3));
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
            ->with(['bookings', 'payments', 'documents'])
            ->get();
    }

    /**
     * Get customer bookings with filters
     */
    public function getCustomerBookings(int $customerId, array $filters = [], int $perPage = 15)
    {
        $query = $this->model->findOrFail($customerId)->bookings();
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }
        
        return $query->with(['vehicle', 'route', 'shipment'])
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * Get customer communication history
     */
    public function getCommunicationHistory(int $customerId, int $perPage = 20)
    {
        $customer = $this->model->findOrFail($customerId);
        
        return $customer->chatMessages()
                       ->with(['booking'])
                       ->orderBy('created_at', 'desc')
                       ->paginate($perPage);
    }

    /**
     * Get customer statistics with filters
     */
    public function getCustomerStatistics(array $filters = []): array
    {
        $query = $this->model->newQuery();
        $query = $this->applyFilters($query, $filters);
        
        $totalCustomers = $query->count();
        $activeCustomers = (clone $query)->where('is_active', true)->count();
        $verifiedCustomers = (clone $query)->where('is_verified', true)->count();
        
        $tierStats = (clone $query)->get()->groupBy(function ($customer) {
            return $customer->getCustomerTier();
        })->map->count();

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'verified_customers' => $verifiedCustomers,
            'inactive_customers' => $totalCustomers - $activeCustomers,
            'unverified_customers' => $totalCustomers - $verifiedCustomers,
            'tier_breakdown' => $tierStats,
            'average_total_spent' => (clone $query)->avg('total_spent'),
            'average_bookings_per_customer' => (clone $query)->avg('total_bookings'),
            'customers_with_bookings' => (clone $query)->where('total_bookings', '>', 0)->count(),
            'new_customers_this_month' => (clone $query)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    /**
     * Get customers with booking history
     */
    public function getWithBookingHistory(): Collection
    {
        return $this->model
            ->with(['bookings' => function ($query) {
                $query->with(['vehicle', 'route', 'shipment'])
                      ->orderBy('created_at', 'desc');
            }])
            ->whereHas('bookings')
            ->get();
    }

    /**
     * Get top customers by spending
     */
    public function getTopCustomersBySpending(int $limit = 10): Collection
    {
        return $this->model
            ->orderBy('total_spent', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get customers with pending payments
     */
    public function getWithPendingPayments(): Collection
    {
        return $this->model
            ->whereHas('payments', function ($query) {
                $query->where('status', 'pending');
            })
            ->with(['payments' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->get();
    }

    /**
     * Get customers requiring attention
     */
    public function getRequiringAttention(): Collection
    {
        return $this->model
            ->where(function ($query) {
                $query->where('is_active', false)
                      ->orWhere('is_verified', false)
                      ->orWhereHas('bookings', function ($bookingQuery) {
                          $bookingQuery->where('status', 'pending')
                                      ->where('created_at', '<', now()->subDays(3));
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
            ->with(['bookings', 'payments', 'documents'])
            ->get();
    }

    /**
     * Get customer acquisition trends
     */
    public function getAcquisitionTrends(int $days = 30): Collection
    {
        return $this->model
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as new_customers'),
                DB::raw('COUNT(CASE WHEN is_verified = 1 THEN 1 END) as verified_customers')
            ])
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get customer lifetime value analysis
     */
    public function getLifetimeValueAnalysis(): Collection
    {
        return $this->model
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'total_spent',
                'total_bookings',
                'created_at',
                DB::raw('DATEDIFF(NOW(), created_at) as days_as_customer'),
                DB::raw('total_spent / GREATEST(total_bookings, 1) as average_order_value'),
                DB::raw('total_spent / GREATEST(DATEDIFF(NOW(), created_at), 1) as daily_value')
            ])
            ->where('total_bookings', '>', 0)
            ->orderBy('total_spent', 'desc')
            ->get();
    }

    /**
     * Get customers by country
     */
    public function getByCountry(string $country): Collection
    {
        return $this->model
            ->where('country', 'LIKE', "%{$country}%")
            ->get();
    }

    /**
     * Get customer retention metrics
     */
    public function getRetentionMetrics(): array
    {
        $totalCustomers = $this->model->count();
        $repeatCustomers = $this->model->where('total_bookings', '>', 1)->count();
        
        $cohortAnalysis = $this->model
            ->select([
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as cohort_month'),
                DB::raw('COUNT(*) as customers'),
                DB::raw('SUM(CASE WHEN total_bookings > 1 THEN 1 ELSE 0 END) as repeat_customers'),
                DB::raw('AVG(total_spent) as avg_ltv'),
                DB::raw('AVG(total_bookings) as avg_bookings')
            ])
            ->groupBy('cohort_month')
            ->orderBy('cohort_month')
            ->get();

        return [
            'total_customers' => $totalCustomers,
            'repeat_customers' => $repeatCustomers,
            'retention_rate' => $totalCustomers > 0 ? ($repeatCustomers / $totalCustomers) * 100 : 0,
            'cohort_analysis' => $cohortAnalysis,
            'churn_indicators' => $this->getChurnIndicators(),
        ];
    }

    /**
     * Get customers with recent activity
     */
    public function getWithRecentActivity(int $days = 30): Collection
    {
        return $this->model
            ->where(function ($query) use ($days) {
                $query->where('last_login_at', '>=', now()->subDays($days))
                      ->orWhereHas('bookings', function ($bookingQuery) use ($days) {
                          $bookingQuery->where('created_at', '>=', now()->subDays($days));
                      })
                      ->orWhereHas('payments', function ($paymentQuery) use ($days) {
                          $paymentQuery->where('created_at', '>=', now()->subDays($days));
                      });
            })
            ->with(['bookings' => function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            }])
            ->get();
    }

    /**
     * Get churn indicators
     */
    private function getChurnIndicators(): array
    {
        $inactiveCustomers = $this->model
            ->where('last_login_at', '<', now()->subDays(90))
            ->orWhereDoesntHave('bookings', function ($query) {
                $query->where('created_at', '>=', now()->subYear());
            })
            ->count();

        $atRiskCustomers = $this->model
            ->where('last_login_at', '<', now()->subDays(30))
            ->where('last_login_at', '>=', now()->subDays(90))
            ->count();

        return [
            'inactive_customers' => $inactiveCustomers,
            'at_risk_customers' => $atRiskCustomers,
            'churn_rate' => $this->model->count() > 0 ? ($inactiveCustomers / $this->model->count()) * 100 : 0,
        ];
    }

    /**
     * Apply search filter for customers
     */
    protected function applySearchFilter(Builder $query, string $searchTerm): void
    {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('first_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('email', 'LIKE', "%{$searchTerm}%")
              ->orWhere('phone', 'LIKE', "%{$searchTerm}%")
              ->orWhere('id_number', 'LIKE', "%{$searchTerm}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"]);
        });
    }

    /**
     * Apply custom filters for customers
     */
    protected function applyCustomFilter(Builder $query, string $key, $value): void
    {
        switch ($key) {
            case 'is_active':
                $query->where('is_active', (bool) $value);
                break;
                
            case 'is_verified':
                $query->where('is_verified', (bool) $value);
                break;
                
            case 'customer_tier':
                $this->applyTierFilter($query, $value);
                break;
                
            case 'country':
                $query->where('country', 'LIKE', "%{$value}%");
                break;
                
            case 'city':
                $query->where('city', 'LIKE', "%{$value}%");
                break;
                
            case 'has_bookings':
                if ((bool) $value) {
                    $query->where('total_bookings', '>', 0);
                } else {
                    $query->where('total_bookings', '=', 0);
                }
                break;
                
            case 'has_active_bookings':
                if ((bool) $value) {
                    $query->whereHas('bookings', function ($bookingQuery) {
                        $bookingQuery->whereNotIn('status', ['delivered', 'cancelled']);
                    });
                } else {
                    $query->whereDoesntHave('bookings', function ($bookingQuery) {
                        $bookingQuery->whereNotIn('status', ['delivered', 'cancelled']);
                    });
                }
                break;
                
            case 'newsletter_subscribed':
                $query->where('newsletter_subscribed', (bool) $value);
                break;
                
            case 'last_login_from':
                $query->where('last_login_at', '>=', $value);
                break;
                
            case 'last_login_to':
                $query->where('last_login_at', '<=', $value);
                break;
                
            case 'total_spent_min':
                $query->where('total_spent', '>=', (float) $value);
                break;
                
            case 'total_spent_max':
                $query->where('total_spent', '<=', (float) $value);
                break;
                
            case 'total_bookings_min':
                $query->where('total_bookings', '>=', (int) $value);
                break;
                
            case 'total_bookings_max':
                $query->where('total_bookings', '<=', (int) $value);
                break;
                
            case 'start_date':
                $query->where('created_at', '>=', $value);
                break;
                
            case 'end_date':
                $query->where('created_at', '<=', $value);
                break;
                
            // Legacy support
            case 'tier':
                $this->applyTierFilter($query, $value);
                break;
                
            case 'spent_min':
                $query->where('total_spent', '>=', $value);
                break;
                
            case 'spent_max':
                $query->where('total_spent', '<=', $value);
                break;
                
            default:
                // Handle any other custom filters
                if (in_array($key, $this->model->getFillable())) {
                    $query->where($key, $value);
                }
                break;
        }
    }

    /**
     * Apply tier filter
     */
    private function applyTierFilter(Builder $query, string $tier): void
    {
        switch ($tier) {
            case 'platinum':
                $query->where('total_spent', '>=', 50000);
                break;
            case 'gold':
                $query->whereBetween('total_spent', [25000, 49999.99]);
                break;
            case 'silver':
                $query->whereBetween('total_spent', [10000, 24999.99]);
                break;
            case 'bronze':
                $query->where('total_spent', '<', 10000);
                break;
        }
    }
}