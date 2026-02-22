<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Booking Repository Interface
 */
interface BookingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get bookings by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get active bookings (not cancelled or delivered)
     */
    public function getActive(): Collection;

    /**
     * Get completed bookings
     */
    public function getCompleted(): Collection;

    /**
     * Get bookings in progress
     */
    public function getInProgress(): Collection;

    /**
     * Get bookings by customer
     */
    public function getByCustomer(int $customerId): Collection;

    /**
     * Get bookings by route
     */
    public function getByRoute(int $routeId): Collection;

    /**
     * Get overdue bookings
     */
    public function getOverdue(): Collection;

    /**
     * Update booking status
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Get bookings with full relationships
     */
    public function getWithFullRelations(): Collection;

    /**
     * Get booking statistics
     */
    public function getStatistics(array $filters = []): array;

    /**
     * Get revenue by period
     */
    public function getRevenueByPeriod(string $period = 'month'): Collection;

    /**
     * Get booking trends
     */
    public function getBookingTrends(int $days = 30): Collection;

    /**
     * Get bookings by payment status
     */
    public function getByPaymentStatus(string $paymentStatus): Collection;

    /**
     * Search bookings by reference or customer
     */
    public function searchBookings(string $query): Collection;

    /**
     * Get recent bookings
     */
    public function getRecent(int $limit = 10): Collection;

    /**
     * Get bookings requiring attention
     */
    public function getRequiringAttention(): Collection;

    /**
     * Get bookings by date range with analytics
     */
    public function getAnalyticsByDateRange(string $startDate, string $endDate): array;
}