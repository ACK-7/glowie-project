<?php

namespace App\Repositories\Contracts;

use App\Models\Quote;
use Illuminate\Database\Eloquent\Collection;

/**
 * Quote Repository Interface
 */
interface QuoteRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get quotes by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get pending quotes
     */
    public function getPending(): Collection;

    /**
     * Get approved quotes
     */
    public function getApproved(): Collection;

    /**
     * Get expired quotes
     */
    public function getExpired(): Collection;

    /**
     * Get valid quotes
     */
    public function getValid(): Collection;

    /**
     * Get quotes by customer
     */
    public function getByCustomer(int $customerId): Collection;

    /**
     * Get quotes by route
     */
    public function getByRoute(int $routeId): Collection;

    /**
     * Get quotes expiring soon
     */
    public function getExpiringSoon(int $days = 7): Collection;

    /**
     * Get quote conversion statistics
     */
    public function getConversionStatistics(): array;

    /**
     * Get quote trends
     */
    public function getQuoteTrends(int $days = 30): Collection;

    /**
     * Search quotes
     */
    public function searchQuotes(string $query): Collection;

    /**
     * Get recent quotes
     */
    public function getRecent(int $limit = 10): Collection;

    /**
     * Get quotes requiring approval
     */
    public function getRequiringApproval(): Collection;

    /**
     * Get quote analytics by date range
     */
    public function getAnalyticsByDateRange(string $startDate, string $endDate): array;

    /**
     * Get average quote value by route
     */
    public function getAverageValueByRoute(): Collection;
}