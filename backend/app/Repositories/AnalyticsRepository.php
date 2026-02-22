<?php

namespace App\Repositories;

use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Models\Booking;
use App\Models\Quote;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\Payment;
use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Analytics Repository Implementation
 */
class AnalyticsRepository implements AnalyticsRepositoryInterface
{
    /**
     * Get dashboard KPIs
     */
    public function getDashboardKPIs(array $filters = []): array
    {
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();
        $yearToDate = now()->startOfYear();

        return [
            'bookings' => [
                'total' => Booking::count(),
                'current_month' => Booking::where('created_at', '>=', $currentMonth)->count(),
                'previous_month' => Booking::whereBetween('created_at', [$previousMonth, $currentMonth])->count(),
                'ytd' => Booking::where('created_at', '>=', $yearToDate)->count(),
                'active' => Booking::active()->count(),
                'pending' => Booking::where('status', Booking::STATUS_PENDING)->count(),
            ],
            'quotes' => [
                'total' => Quote::count(),
                'current_month' => Quote::where('created_at', '>=', $currentMonth)->count(),
                'previous_month' => Quote::whereBetween('created_at', [$previousMonth, $currentMonth])->count(),
                'ytd' => Quote::where('created_at', '>=', $yearToDate)->count(),
                'pending' => Quote::where('status', Quote::STATUS_PENDING)->count(),
                'conversion_rate' => $this->getQuoteConversionRate(),
            ],
            'shipments' => [
                'total' => Shipment::count(),
                'active' => Shipment::active()->count(),
                'in_transit' => Shipment::where('status', Shipment::STATUS_IN_TRANSIT)->count(),
                'delayed' => Shipment::where('status', Shipment::STATUS_DELAYED)->count(),
                'delivered_this_month' => Shipment::where('status', Shipment::STATUS_DELIVERED)
                    ->where('actual_arrival', '>=', $currentMonth)->count(),
            ],
            'revenue' => [
                'total' => Payment::where('status', Payment::STATUS_COMPLETED)->sum('amount'),
                'current_month' => Payment::where('status', Payment::STATUS_COMPLETED)
                    ->where('payment_date', '>=', $currentMonth)->sum('amount'),
                'previous_month' => Payment::where('status', Payment::STATUS_COMPLETED)
                    ->whereBetween('payment_date', [$previousMonth, $currentMonth])->sum('amount'),
                'ytd' => Payment::where('status', Payment::STATUS_COMPLETED)
                    ->where('payment_date', '>=', $yearToDate)->sum('amount'),
                'outstanding' => Booking::sum(DB::raw('total_amount - paid_amount')),
            ],
            'customers' => [
                'total' => Customer::count(),
                'active' => Customer::active()->count(),
                'new_this_month' => Customer::where('created_at', '>=', $currentMonth)->count(),
                'with_bookings' => Customer::whereHas('bookings')->count(),
            ],
        ];
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $payments = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get();

        $dailyRevenue = Payment::select([
                DB::raw('DATE(payment_date) as date'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as transactions')
            ])
            ->where('status', Payment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $revenueByMethod = Payment::select([
                'payment_method',
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as transactions'),
                DB::raw('AVG(amount) as avg_amount')
            ])
            ->where('status', Payment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->get();

        return [
            'total_revenue' => $payments->sum('amount'),
            'total_transactions' => $payments->count(),
            'average_transaction' => $payments->avg('amount'),
            'daily_revenue' => $dailyRevenue,
            'revenue_by_method' => $revenueByMethod,
            'growth_rate' => $this->calculateGrowthRate($startDate, $endDate),
        ];
    }

    /**
     * Get booking analytics
     */
    public function getBookingAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $bookings = Booking::whereBetween('created_at', [$startDate, $endDate])->get();

        $bookingsByStatus = Booking::select([
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as revenue')
            ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();

        $bookingsByRoute = Booking::join('routes', 'bookings.route_id', '=', 'routes.id')
            ->select([
                'routes.origin_country',
                'routes.destination_country',
                DB::raw('COUNT(bookings.id) as bookings_count'),
                DB::raw('SUM(bookings.total_amount) as revenue'),
                DB::raw('AVG(bookings.total_amount) as avg_value')
            ])
            ->whereBetween('bookings.created_at', [$startDate, $endDate])
            ->groupBy(['routes.origin_country', 'routes.destination_country'])
            ->orderBy('bookings_count', 'desc')
            ->get();

        return [
            'total_bookings' => $bookings->count(),
            'total_value' => $bookings->sum('total_amount'),
            'average_value' => $bookings->avg('total_amount'),
            'bookings_by_status' => $bookingsByStatus,
            'bookings_by_route' => $bookingsByRoute,
            'completion_rate' => $this->getBookingCompletionRate($startDate, $endDate),
        ];
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $customers = Customer::whereBetween('created_at', [$startDate, $endDate])->get();

        $customersByTier = Customer::get()->groupBy(function ($customer) {
            return $customer->getCustomerTier();
        })->map->count();

        $topCustomers = Customer::orderBy('total_spent', 'desc')->limit(10)->get();

        $customerRetention = $this->getCustomerRetentionMetrics($startDate, $endDate);

        return [
            'new_customers' => $customers->count(),
            'customers_by_tier' => $customersByTier,
            'top_customers' => $topCustomers,
            'retention_metrics' => $customerRetention,
            'average_ltv' => Customer::avg('total_spent'),
            'repeat_customer_rate' => $this->getRepeatCustomerRate(),
        ];
    }

    /**
     * Get shipment analytics
     */
    public function getShipmentAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $shipments = Shipment::whereBetween('created_at', [$startDate, $endDate])->get();

        $deliveryPerformance = Shipment::where('status', Shipment::STATUS_DELIVERED)
            ->whereNotNull('actual_arrival')
            ->whereNotNull('estimated_arrival')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $onTimeDeliveries = $deliveryPerformance->filter(function ($shipment) {
            return $shipment->actual_arrival <= $shipment->estimated_arrival;
        });

        return [
            'total_shipments' => $shipments->count(),
            'delivered_shipments' => $shipments->where('status', Shipment::STATUS_DELIVERED)->count(),
            'delayed_shipments' => $shipments->where('status', Shipment::STATUS_DELAYED)->count(),
            'on_time_delivery_rate' => $deliveryPerformance->count() > 0 ? 
                ($onTimeDeliveries->count() / $deliveryPerformance->count()) * 100 : 0,
            'average_transit_time' => $this->getAverageTransitTime($startDate, $endDate),
            'carrier_performance' => $this->getCarrierPerformance($startDate, $endDate),
        ];
    }

    /**
     * Get route performance analytics
     */
    public function getRoutePerformanceAnalytics(): array
    {
        return DB::table('routes')
            ->leftJoin('bookings', 'routes.id', '=', 'bookings.route_id')
            ->leftJoin('quotes', 'routes.id', '=', 'quotes.route_id')
            ->select([
                'routes.id',
                'routes.origin_country',
                'routes.destination_country',
                'routes.base_price',
                DB::raw('COUNT(DISTINCT bookings.id) as total_bookings'),
                DB::raw('COUNT(DISTINCT quotes.id) as total_quotes'),
                DB::raw('SUM(bookings.total_amount) as total_revenue'),
                DB::raw('AVG(bookings.total_amount) as avg_booking_value'),
                DB::raw('COUNT(CASE WHEN quotes.status = "converted" THEN 1 END) as converted_quotes')
            ])
            ->groupBy(['routes.id', 'routes.origin_country', 'routes.destination_country', 'routes.base_price'])
            ->orderBy('total_revenue', 'desc')
            ->get()
            ->map(function ($route) {
                $route->conversion_rate = $route->total_quotes > 0 ? 
                    ($route->converted_quotes / $route->total_quotes) * 100 : 0;
                return $route;
            });
    }

    /**
     * Get conversion funnel analytics
     */
    public function getConversionFunnelAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $quotes = Quote::whereBetween('created_at', [$startDate, $endDate])->count();
        $approvedQuotes = Quote::where('status', Quote::STATUS_APPROVED)
            ->whereBetween('created_at', [$startDate, $endDate])->count();
        $convertedQuotes = Quote::where('status', Quote::STATUS_CONVERTED)
            ->whereBetween('created_at', [$startDate, $endDate])->count();
        $completedBookings = Booking::where('status', Booking::STATUS_DELIVERED)
            ->whereBetween('created_at', [$startDate, $endDate])->count();

        return [
            'quotes_created' => $quotes,
            'quotes_approved' => $approvedQuotes,
            'quotes_converted' => $convertedQuotes,
            'bookings_completed' => $completedBookings,
            'approval_rate' => $quotes > 0 ? ($approvedQuotes / $quotes) * 100 : 0,
            'conversion_rate' => $approvedQuotes > 0 ? ($convertedQuotes / $approvedQuotes) * 100 : 0,
            'completion_rate' => $convertedQuotes > 0 ? ($completedBookings / $convertedQuotes) * 100 : 0,
        ];
    }

    /**
     * Get operational metrics
     */
    public function getOperationalMetrics(): array
    {
        return [
            'pending_approvals' => Quote::where('status', Quote::STATUS_PENDING)->count(),
            'pending_documents' => Document::where('status', Document::STATUS_PENDING)->count(),
            'overdue_payments' => Payment::where('status', Payment::STATUS_PENDING)
                ->where('created_at', '<', now()->subDays(30))->count(),
            'delayed_shipments' => Shipment::where('status', Shipment::STATUS_DELAYED)->count(),
            'expiring_documents' => Document::where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>', now())->count(),
            'active_customers' => Customer::where('last_login_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /**
     * Get financial summary
     */
    public function getFinancialSummary(Carbon $startDate, Carbon $endDate): array
    {
        $revenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');

        $outstanding = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->sum(DB::raw('total_amount - paid_amount'));

        $refunds = Payment::where('status', Payment::STATUS_REFUNDED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        return [
            'total_revenue' => $revenue,
            'outstanding_amount' => $outstanding,
            'refunded_amount' => abs($refunds),
            'net_revenue' => $revenue - abs($refunds),
            'collection_rate' => ($revenue / ($revenue + $outstanding)) * 100,
        ];
    }

    /**
     * Get trend analysis
     */
    public function getTrendAnalysis(string $metric, int $days = 30): Collection
    {
        switch ($metric) {
            case 'bookings':
                return Booking::select([
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as value')
                ])
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            case 'revenue':
                return Payment::select([
                    DB::raw('DATE(payment_date) as date'),
                    DB::raw('SUM(amount) as value')
                ])
                ->where('status', Payment::STATUS_COMPLETED)
                ->where('payment_date', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            default:
                return collect();
        }
    }

    /**
     * Get comparative analysis
     */
    public function getComparativeAnalysis(Carbon $currentStart, Carbon $currentEnd, Carbon $previousStart, Carbon $previousEnd): array
    {
        $currentMetrics = $this->getPeriodMetrics($currentStart, $currentEnd);
        $previousMetrics = $this->getPeriodMetrics($previousStart, $previousEnd);

        return [
            'current_period' => $currentMetrics,
            'previous_period' => $previousMetrics,
            'growth_rates' => $this->calculateGrowthRates($currentMetrics, $previousMetrics),
        ];
    }

    /**
     * Get export data for reports
     */
    public function getExportData(string $reportType, array $filters = []): Collection
    {
        switch ($reportType) {
            case 'bookings':
                return Booking::with(['customer', 'vehicle', 'route', 'shipment'])
                    ->when(isset($filters['start_date']), function ($query) use ($filters) {
                        return $query->where('created_at', '>=', $filters['start_date']);
                    })
                    ->when(isset($filters['end_date']), function ($query) use ($filters) {
                        return $query->where('created_at', '<=', $filters['end_date']);
                    })
                    ->get();

            case 'payments':
                return Payment::with(['booking.customer', 'customer'])
                    ->when(isset($filters['start_date']), function ($query) use ($filters) {
                        return $query->where('payment_date', '>=', $filters['start_date']);
                    })
                    ->when(isset($filters['end_date']), function ($query) use ($filters) {
                        return $query->where('payment_date', '<=', $filters['end_date']);
                    })
                    ->get();

            default:
                return collect();
        }
    }

    /**
     * Helper methods
     */
    private function getQuoteConversionRate(): float
    {
        $totalQuotes = Quote::count();
        $convertedQuotes = Quote::where('status', Quote::STATUS_CONVERTED)->count();
        
        return $totalQuotes > 0 ? ($convertedQuotes / $totalQuotes) * 100 : 0;
    }

    private function calculateGrowthRate(Carbon $startDate, Carbon $endDate): float
    {
        $days = $startDate->diffInDays($endDate);
        $previousStart = $startDate->copy()->subDays($days);
        $previousEnd = $startDate->copy()->subDay();

        $currentRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');

        $previousRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [$previousStart, $previousEnd])
            ->sum('amount');

        return $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
    }

    private function getBookingCompletionRate(Carbon $startDate, Carbon $endDate): float
    {
        $totalBookings = Booking::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedBookings = Booking::where('status', Booking::STATUS_DELIVERED)
            ->whereBetween('created_at', [$startDate, $endDate])->count();

        return $totalBookings > 0 ? ($completedBookings / $totalBookings) * 100 : 0;
    }

    private function getCustomerRetentionMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();
        $repeatCustomers = Customer::where('total_bookings', '>', 1)
            ->whereBetween('created_at', [$startDate, $endDate])->count();

        return [
            'new_customers' => $newCustomers,
            'repeat_customers' => $repeatCustomers,
            'retention_rate' => $newCustomers > 0 ? ($repeatCustomers / $newCustomers) * 100 : 0,
        ];
    }

    private function getRepeatCustomerRate(): float
    {
        $totalCustomers = Customer::count();
        $repeatCustomers = Customer::where('total_bookings', '>', 1)->count();

        return $totalCustomers > 0 ? ($repeatCustomers / $totalCustomers) * 100 : 0;
    }

    private function getAverageTransitTime(Carbon $startDate, Carbon $endDate): float
    {
        return Shipment::where('status', Shipment::STATUS_DELIVERED)
            ->whereNotNull('departure_date')
            ->whereNotNull('actual_arrival')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->avg(function ($shipment) {
                return $shipment->departure_date->diffInDays($shipment->actual_arrival);
            }) ?: 0;
    }

    private function getCarrierPerformance(Carbon $startDate, Carbon $endDate): Collection
    {
        return Shipment::select([
            'carrier_name',
            DB::raw('COUNT(*) as total_shipments'),
            DB::raw('COUNT(CASE WHEN status = "delivered" THEN 1 END) as delivered'),
            DB::raw('COUNT(CASE WHEN status = "delayed" THEN 1 END) as delayed')
        ])
        ->whereNotNull('carrier_name')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('carrier_name')
        ->get();
    }

    private function getPeriodMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'bookings' => Booking::whereBetween('created_at', [$startDate, $endDate])->count(),
            'revenue' => Payment::where('status', Payment::STATUS_COMPLETED)
                ->whereBetween('payment_date', [$startDate, $endDate])->sum('amount'),
            'customers' => Customer::whereBetween('created_at', [$startDate, $endDate])->count(),
            'quotes' => Quote::whereBetween('created_at', [$startDate, $endDate])->count(),
        ];
    }

    private function calculateGrowthRates(array $current, array $previous): array
    {
        $growthRates = [];
        
        foreach ($current as $key => $value) {
            $previousValue = $previous[$key] ?? 0;
            $growthRates[$key] = $previousValue > 0 ? (($value - $previousValue) / $previousValue) * 100 : 0;
        }
        
        return $growthRates;
    }
}