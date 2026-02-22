<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Analytics Repository Interface
 */
interface AnalyticsRepositoryInterface
{
    /**
     * Get dashboard KPIs
     */
    public function getDashboardKPIs(array $filters = []): array;

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(Carbon $startDate, Carbon $endDate): array;

    /**
     * Get booking analytics
     */
    public function getBookingAnalytics(Carbon $startDate, Carbon $endDate): array;

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(Carbon $startDate, Carbon $endDate): array;

    /**
     * Get shipment analytics
     */
    public function getShipmentAnalytics(Carbon $startDate, Carbon $endDate): array;

    /**
     * Get route performance analytics
     */
    public function getRoutePerformanceAnalytics(): array;

    /**
     * Get conversion funnel analytics
     */
    public function getConversionFunnelAnalytics(Carbon $startDate, Carbon $endDate): array;

    /**
     * Get operational metrics
     */
    public function getOperationalMetrics(): array;

    /**
     * Get financial summary
     */
    public function getFinancialSummary(Carbon $startDate, Carbon $endDate): array;

    /**
     * Get trend analysis
     */
    public function getTrendAnalysis(string $metric, int $days = 30): Collection;

    /**
     * Get comparative analysis
     */
    public function getComparativeAnalysis(Carbon $currentStart, Carbon $currentEnd, Carbon $previousStart, Carbon $previousEnd): array;

    /**
     * Get export data for reports
     */
    public function getExportData(string $reportType, array $filters = []): Collection;
}