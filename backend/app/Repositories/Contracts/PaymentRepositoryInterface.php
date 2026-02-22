<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Payment Repository Interface
 */
interface PaymentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get payments by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get pending payments
     */
    public function getPending(): Collection;

    /**
     * Get completed payments
     */
    public function getCompleted(): Collection;

    /**
     * Get failed payments
     */
    public function getFailed(): Collection;

    /**
     * Get refunded payments
     */
    public function getRefunded(): Collection;

    /**
     * Get payments by method
     */
    public function getByMethod(string $method): Collection;

    /**
     * Get payments by customer
     */
    public function getByCustomer(int $customerId): Collection;

    /**
     * Get payments by booking
     */
    public function getByBooking(int $bookingId): Collection;

    /**
     * Get overdue payments
     */
    public function getOverdue(int $days = 30): Collection;

    /**
     * Get payments by amount range
     */
    public function getByAmountRange(float $minAmount, float $maxAmount): Collection;

    /**
     * Get total revenue
     */
    public function getTotalRevenue(Carbon $startDate = null, Carbon $endDate = null): float;

    /**
     * Get revenue by method
     */
    public function getRevenueByMethod(Carbon $startDate = null, Carbon $endDate = null): array;

    /**
     * Get payment statistics
     */
    public function getPaymentStatistics(): array;

    /**
     * Get revenue trends
     */
    public function getRevenueTrends(int $days = 30): Collection;

    /**
     * Get payment method performance
     */
    public function getPaymentMethodPerformance(): Collection;

    /**
     * Search payments
     */
    public function searchPayments(string $query): Collection;

    /**
     * Get recent payments
     */
    public function getRecent(int $limit = 10): Collection;

    /**
     * Get payments requiring attention
     */
    public function getRequiringAttention(): Collection;

    /**
     * Find payment by reference
     */
    public function findByReference(string $reference): ?Payment;
}