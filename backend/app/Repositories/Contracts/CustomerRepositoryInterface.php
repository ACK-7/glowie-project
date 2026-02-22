<?php

namespace App\Repositories\Contracts;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

/**
 * Customer Repository Interface
 */
interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get active customers
     */
    public function getActive(): Collection;

    /**
     * Get verified customers
     */
    public function getVerified(): Collection;

    /**
     * Get customers by tier
     */
    public function getByTier(string $tier): Collection;

    /**
     * Search customers by multiple fields
     */
    public function searchCustomers(string $query, array $fields = ['name', 'email', 'phone']): Collection;

    /**
     * Get customers by tier
     */
    public function getCustomersByTier(string $tier): Collection;

    /**
     * Get customers requiring attention
     */
    public function getCustomersRequiringAttention(): Collection;

    /**
     * Get customer bookings with filters
     */
    public function getCustomerBookings(int $customerId, array $filters = [], int $perPage = 15);

    /**
     * Get customer communication history
     */
    public function getCommunicationHistory(int $customerId, int $perPage = 20);

    /**
     * Get customer statistics with filters
     */
    public function getCustomerStatistics(array $filters = []): array;

    /**
     * Get customers with booking history
     */
    public function getWithBookingHistory(): Collection;

    /**
     * Get top customers by spending
     */
    public function getTopCustomersBySpending(int $limit = 10): Collection;

    /**
     * Get customers with pending payments
     */
    public function getWithPendingPayments(): Collection;

    /**
     * Get customers requiring attention
     */
    public function getRequiringAttention(): Collection;

    /**
     * Get customer acquisition trends
     */
    public function getAcquisitionTrends(int $days = 30): Collection;

    /**
     * Get customer lifetime value analysis
     */
    public function getLifetimeValueAnalysis(): Collection;

    /**
     * Get customers by country
     */
    public function getByCountry(string $country): Collection;

    /**
     * Get customer retention metrics
     */
    public function getRetentionMetrics(): array;

    /**
     * Get customers with recent activity
     */
    public function getWithRecentActivity(int $days = 30): Collection;
}