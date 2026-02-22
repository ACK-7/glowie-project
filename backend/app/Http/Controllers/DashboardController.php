<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseApiController;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Services\BookingService;
use App\Services\QuoteService;
use App\Services\PaymentService;
use App\Services\ShipmentService;
use App\Models\Booking;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Dashboard Controller
 * 
 * Provides comprehensive KPI endpoints for the admin dashboard including:
 * - Dashboard statistics aggregation (bookings, shipments, quotes, revenue)
 * - Time period comparisons (current month, previous month, YTD)
 * - Recent activity feeds with proper pagination
 * 
 * Requirements: 1.1, 1.2, 1.4
 */
class DashboardController extends BaseApiController
{
    public function __construct(
        private AnalyticsRepositoryInterface $analyticsRepository,
        private BookingService $bookingService,
        private QuoteService $quoteService,
        private PaymentService $paymentService,
        private ShipmentService $shipmentService,
        private \App\Services\AdvancedAnalyticsService $advancedAnalyticsService
    ) {}

    /**
     * Test endpoint to verify controller is working
     */
    public function test(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Dashboard controller is working',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get comprehensive dashboard statistics with time period comparisons
     * 
     * @return JsonResponse
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->advancedAnalyticsService->getDashboardAnalytics();

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Dashboard statistics retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve dashboard statistics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard statistics: ' . $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Get KPI metrics with current month, previous month, and YTD comparisons
     * 
     * @return JsonResponse
     */
    public function getKPIMetrics(): JsonResponse
    {
        try {
            $currentMonth = now()->startOfMonth();
            $previousMonth = now()->subMonth()->startOfMonth();
            $yearToDate = now()->startOfYear();
            $previousYearToDate = now()->subYear()->startOfYear();

            $kpis = [
                'bookings' => $this->getBookingKPIs($currentMonth, $previousMonth, $yearToDate, $previousYearToDate),
                'quotes' => $this->getQuoteKPIs($currentMonth, $previousMonth, $yearToDate, $previousYearToDate),
                'shipments' => $this->getShipmentKPIs($currentMonth, $previousMonth, $yearToDate, $previousYearToDate),
                'revenue' => $this->getRevenueKPIs($currentMonth, $previousMonth, $yearToDate, $previousYearToDate),
                'customers' => $this->getCustomerKPIs($currentMonth, $previousMonth, $yearToDate, $previousYearToDate),
                'operational' => $this->getOperationalKPIs()
            ];

            return $this->successResponse($kpis, 'KPI metrics retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve KPI metrics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return $this->errorResponse('Failed to retrieve KPI metrics', 500);
        }
    }

    /**
     * Get recent activity feeds with pagination
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecentActivity(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $type = $request->get('type', 'all'); // all, bookings, quotes, shipments, payments
            
            $activities = [
                'recent_bookings' => $this->getRecentBookings($limit),
                'recent_quotes' => $this->getRecentQuotes($limit),
                'recent_shipments' => $this->getRecentShipmentUpdates($limit),
                'recent_payments' => $this->getRecentPayments($limit),
                'system_alerts' => $this->getSystemAlerts($limit)
            ];

            // Filter by type if specified
            if ($type !== 'all' && isset($activities["recent_{$type}"])) {
                $activities = ["recent_{$type}" => $activities["recent_{$type}"]];
            }

            return $this->successResponse($activities, 'Recent activity retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve recent activity', [
                'error' => $e->getMessage(),
                'type' => $type ?? 'all',
                'user_id' => auth()->id()
            ]);
            
            return $this->errorResponse('Failed to retrieve recent activity', 500);
        }
    }

    /**
     * Get revenue analytics with detailed breakdowns
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getRevenueAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'current_month');
            $dates = $this->getPeriodDates($period);
            
            $analytics = $this->analyticsRepository->getRevenueAnalytics($dates['start'], $dates['end']);
            
            // Add additional revenue insights
            $analytics['insights'] = [
                'top_revenue_routes' => $this->getTopRevenueRoutes($dates['start'], $dates['end']),
                'payment_method_distribution' => $this->getPaymentMethodDistribution($dates['start'], $dates['end']),
                'outstanding_payments' => $this->getOutstandingPaymentsSummary(),
                'revenue_forecast' => $this->getRevenueForecast()
            ];

            return $this->successResponse($analytics, 'Revenue analytics retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve revenue analytics', [
                'error' => $e->getMessage(),
                'period' => $period ?? 'current_month',
                'user_id' => auth()->id()
            ]);
            
            return $this->errorResponse('Failed to retrieve revenue analytics', 500);
        }
    }

    /**
     * Get operational metrics and alerts
     * 
     * @return JsonResponse
     */
    public function getOperationalMetrics(): JsonResponse
    {
        try {
            $metrics = [
                'pending_actions' => $this->getPendingActions(),
                'performance_indicators' => $this->getPerformanceIndicators(),
                'capacity_utilization' => $this->getCapacityUtilization(),
                'quality_metrics' => $this->getQualityMetrics(),
                'alerts' => $this->getSystemAlerts(20)
            ];

            return $this->successResponse($metrics, 'Operational metrics retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve operational metrics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return $this->errorResponse('Failed to retrieve operational metrics', 500);
        }
    }

    /**
     * Get chart data for dashboard visualizations
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getChartData(Request $request): JsonResponse
    {
        try {
            $chartType = $request->get('type', 'revenue_trend');
            $period = $request->get('period', '30_days');
            
            $chartData = match ($chartType) {
                'revenue_trend' => $this->getRevenueTrendData($period),
                'booking_status' => $this->getBookingStatusDistribution(),
                'shipment_progress' => $this->getShipmentProgressData(),
                'customer_acquisition' => $this->getCustomerAcquisitionData($period),
                'route_performance' => $this->getRoutePerformanceData(),
                'conversion_funnel' => $this->getConversionFunnelData($period),
                default => throw new \InvalidArgumentException("Invalid chart type: {$chartType}")
            };

            return $this->successResponse($chartData, 'Chart data retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve chart data', [
                'error' => $e->getMessage(),
                'chart_type' => $chartType ?? 'revenue_trend',
                'period' => $period ?? '30_days',
                'user_id' => auth()->id()
            ]);
            
            return $this->errorResponse('Failed to retrieve chart data', 500);
        }
    }

    /**
     * Generate comprehensive dashboard statistics
     */
    private function generateDashboardStatistics(): array
    {
        $kpis = $this->analyticsRepository->getDashboardKPIs();
        
        return [
            'overview' => [
                'total_bookings' => $kpis['bookings']['total'],
                'active_shipments' => $kpis['shipments']['active'],
                'pending_quotes' => $kpis['quotes']['pending'],
                'total_revenue' => $kpis['revenue']['total'],
                'total_customers' => $kpis['customers']['total'],
                'unread_messages' => \App\Models\ChatMessage::unread()->where('sender_type', 'customer')->count(),
                'last_updated' => now()->toISOString()
            ],
            'trends' => [
                'bookings_growth' => $this->calculateGrowthRate(
                    $kpis['bookings']['current_month'],
                    $kpis['bookings']['previous_month']
                ),
                'revenue_growth' => $this->calculateGrowthRate(
                    $kpis['revenue']['current_month'],
                    $kpis['revenue']['previous_month']
                ),
                'customer_growth' => $this->calculateGrowthRate(
                    $kpis['customers']['new_this_month'],
                    Customer::whereBetween('created_at', [
                        now()->subMonths(2)->startOfMonth(),
                        now()->subMonth()->endOfMonth()
                    ])->count()
                )
            ],
            'alerts' => $this->getSystemAlerts(5),
            'quick_stats' => [
                'conversion_rate' => $kpis['quotes']['conversion_rate'],
                'on_time_delivery_rate' => $this->getOnTimeDeliveryRate(),
                'customer_satisfaction' => $this->getCustomerSatisfactionScore(),
                'average_booking_value' => $this->getAverageBookingValue()
            ]
        ];
    }

    /**
     * Get booking KPIs with time comparisons
     */
    private function getBookingKPIs(Carbon $currentMonth, Carbon $previousMonth, Carbon $yearToDate, Carbon $previousYearToDate): array
    {
        $currentMonthEnd = $currentMonth->copy()->endOfMonth();
        $previousMonthEnd = $previousMonth->copy()->endOfMonth();
        $yearToDateEnd = now();
        $previousYearToDateEnd = $previousYearToDate->copy()->addYear()->endOfYear();

        return [
            'total' => Booking::count(),
            'current_month' => Booking::whereBetween('created_at', [$currentMonth, $currentMonthEnd])->count(),
            'previous_month' => Booking::whereBetween('created_at', [$previousMonth, $previousMonthEnd])->count(),
            'ytd' => Booking::whereBetween('created_at', [$yearToDate, $yearToDateEnd])->count(),
            'previous_ytd' => Booking::whereBetween('created_at', [$previousYearToDate, $previousYearToDateEnd])->count(),
            'active' => Booking::whereIn('status', ['confirmed', 'in_transit'])->count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'delivered' => Booking::where('status', 'delivered')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
            'average_value' => Booking::avg('total_amount') ?? 0,
            'completion_rate' => $this->getBookingCompletionRate()
        ];
    }

    /**
     * Get quote KPIs with time comparisons
     */
    private function getQuoteKPIs(Carbon $currentMonth, Carbon $previousMonth, Carbon $yearToDate, Carbon $previousYearToDate): array
    {
        $currentMonthEnd = $currentMonth->copy()->endOfMonth();
        $previousMonthEnd = $previousMonth->copy()->endOfMonth();
        $yearToDateEnd = now();

        return [
            'total' => Quote::count(),
            'current_month' => Quote::whereBetween('created_at', [$currentMonth, $currentMonthEnd])->count(),
            'previous_month' => Quote::whereBetween('created_at', [$previousMonth, $previousMonthEnd])->count(),
            'ytd' => Quote::whereBetween('created_at', [$yearToDate, $yearToDateEnd])->count(),
            'pending' => Quote::where('status', 'pending')->count(),
            'approved' => Quote::where('status', 'approved')->count(),
            'converted' => Quote::where('status', 'converted')->count(),
            'rejected' => Quote::where('status', 'rejected')->count(),
            'expired' => Quote::where('valid_until', '<', now())->where('status', '!=', 'converted')->count(),
            'conversion_rate' => $this->getQuoteConversionRate(),
            'average_value' => Quote::avg('total_amount') ?? 0,
            'approval_rate' => $this->getQuoteApprovalRate()
        ];
    }

    /**
     * Get shipment KPIs with time comparisons
     */
    private function getShipmentKPIs(Carbon $currentMonth, Carbon $previousMonth, Carbon $yearToDate, Carbon $previousYearToDate): array
    {
        $currentMonthEnd = $currentMonth->copy()->endOfMonth();
        $yearToDateEnd = now();

        return [
            'total' => Shipment::count(),
            'active' => Shipment::whereIn('status', ['preparing', 'in_transit', 'customs'])->count(),
            'in_transit' => Shipment::where('status', 'in_transit')->count(),
            'delayed' => Shipment::where('status', 'delayed')->count(),
            'delivered_this_month' => Shipment::where('status', 'delivered')
                ->whereBetween('actual_arrival', [$currentMonth, $currentMonthEnd])->count(),
            'delivered_ytd' => Shipment::where('status', 'delivered')
                ->whereBetween('actual_arrival', [$yearToDate, $yearToDateEnd])->count(),
            'preparing' => Shipment::where('status', 'preparing')->count(),
            'customs' => Shipment::where('status', 'customs')->count(),
            'on_time_delivery_rate' => $this->getOnTimeDeliveryRate(),
            'average_transit_time' => $this->getAverageTransitTime(),
            'delay_rate' => $this->getDelayRate()
        ];
    }

    /**
     * Get revenue KPIs with time comparisons
     */
    private function getRevenueKPIs(Carbon $currentMonth, Carbon $previousMonth, Carbon $yearToDate, Carbon $previousYearToDate): array
    {
        $currentMonthEnd = $currentMonth->copy()->endOfMonth();
        $previousMonthEnd = $previousMonth->copy()->endOfMonth();
        $yearToDateEnd = now();

        return [
            'total' => Payment::where('status', 'completed')->sum('amount') ?? 0,
            'current_month' => Payment::where('status', 'completed')
                ->whereBetween('payment_date', [$currentMonth, $currentMonthEnd])->sum('amount') ?? 0,
            'previous_month' => Payment::where('status', 'completed')
                ->whereBetween('payment_date', [$previousMonth, $previousMonthEnd])->sum('amount') ?? 0,
            'ytd' => Payment::where('status', 'completed')
                ->whereBetween('payment_date', [$yearToDate, $yearToDateEnd])->sum('amount') ?? 0,
            'outstanding' => $this->getOutstandingAmount(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'overdue_payments' => Payment::where('status', 'pending')
                ->where('created_at', '<', now()->subDays(30))->count(),
            'average_payment' => Payment::where('status', 'completed')->avg('amount') ?? 0,
            'collection_rate' => $this->getCollectionRate(),
            'refunded_amount' => Payment::where('status', 'refunded')->sum('amount') ?? 0
        ];
    }

    /**
     * Get customer KPIs with time comparisons
     */
    private function getCustomerKPIs(Carbon $currentMonth, Carbon $previousMonth, Carbon $yearToDate, Carbon $previousYearToDate): array
    {
        $currentMonthEnd = $currentMonth->copy()->endOfMonth();
        $previousMonthEnd = $previousMonth->copy()->endOfMonth();
        $yearToDateEnd = now();

        return [
            'total' => Customer::count(),
            'active' => Customer::where('is_active', true)->count(),
            'new_this_month' => Customer::whereBetween('created_at', [$currentMonth, $currentMonthEnd])->count(),
            'new_previous_month' => Customer::whereBetween('created_at', [$previousMonth, $previousMonthEnd])->count(),
            'new_ytd' => Customer::whereBetween('created_at', [$yearToDate, $yearToDateEnd])->count(),
            'with_bookings' => Customer::whereHas('bookings')->count(),
            'repeat_customers' => Customer::has('bookings', '>', 1)->count(),
            'average_ltv' => Customer::avg('total_spent') ?? 0,
            'retention_rate' => $this->getCustomerRetentionRate(),
            'churn_rate' => $this->getCustomerChurnRate()
        ];
    }

    /**
     * Get operational KPIs
     */
    private function getOperationalKPIs(): array
    {
        return [
            'pending_document_verifications' => Document::where('status', 'pending')->count(),
            'expiring_documents' => Document::where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>', now())->count(),
            'overdue_tasks' => $this->getOverdueTasksCount(),
            'system_health_score' => $this->getSystemHealthScore(),
            'api_response_time' => $this->getAverageApiResponseTime(),
            'error_rate' => $this->getSystemErrorRate()
        ];
    }

    /**
     * Get recent bookings with essential information
     */
    private function getRecentBookings(int $limit): array
    {
        return Booking::with(['customer', 'vehicle', 'route'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'reference' => $booking->booking_reference,
                    'customer_name' => $booking->customer->full_name ?? 'N/A',
                    'customer_email' => $booking->customer->email ?? 'N/A',
                    'vehicle' => ($booking->vehicle->make ?? '') . ' ' . ($booking->vehicle->model ?? ''),
                    'route' => ($booking->route->origin_country ?? '') . ' → ' . ($booking->route->destination_country ?? ''),
                    'status' => $booking->status,
                    'status_label' => ucfirst(str_replace('_', ' ', $booking->status)),
                    'amount' => $booking->total_amount,
                    'currency' => $booking->currency ?? 'USD',
                    'created_at' => $booking->created_at->toISOString(),
                    'created_at_human' => $booking->created_at->diffForHumans()
                ];
            })
            ->toArray();
    }

    /**
     * Get recent quotes with essential information
     */
    private function getRecentQuotes(int $limit): array
    {
        return Quote::with(['customer', 'route'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($quote) {
                return [
                    'id' => $quote->id,
                    'reference' => $quote->quote_reference,
                    'customer_name' => $quote->customer->full_name ?? 'N/A',
                    'customer_email' => $quote->customer->email ?? 'N/A',
                    'route' => ($quote->route->origin_country ?? '') . ' → ' . ($quote->route->destination_country ?? ''),
                    'status' => $quote->status,
                    'status_label' => ucfirst(str_replace('_', ' ', $quote->status)),
                    'amount' => $quote->total_amount,
                    'currency' => $quote->currency ?? 'USD',
                    'valid_until' => $quote->valid_until?->toISOString(),
                    'is_expired' => $quote->valid_until ? $quote->valid_until->isPast() : false,
                    'created_at' => $quote->created_at->toISOString(),
                    'created_at_human' => $quote->created_at->diffForHumans()
                ];
            })
            ->toArray();
    }

    /**
     * Get recent shipment updates
     */
    private function getRecentShipmentUpdates(int $limit): array
    {
        return Shipment::with(['booking.customer'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($shipment) {
                return [
                    'id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'customer_name' => $shipment->booking->customer->full_name ?? 'N/A',
                    'current_location' => $shipment->current_location ?? 'Unknown',
                    'status' => $shipment->status,
                    'status_label' => ucfirst(str_replace('_', ' ', $shipment->status)),
                    'progress_percentage' => $shipment->progress_percentage ?? 0,
                    'estimated_arrival' => $shipment->estimated_arrival?->toISOString(),
                    'is_delayed' => $shipment->is_delayed ?? false,
                    'updated_at' => $shipment->updated_at->toISOString(),
                    'updated_at_human' => $shipment->updated_at->diffForHumans()
                ];
            })
            ->toArray();
    }

    /**
     * Get recent payments
     */
    private function getRecentPayments(int $limit): array
    {
        return Payment::with(['booking.customer', 'customer'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'reference' => $payment->payment_reference,
                    'customer_name' => $payment->customer->full_name ?? $payment->booking->customer->full_name ?? 'N/A',
                    'amount' => $payment->amount,
                    'currency' => $payment->currency ?? 'USD',
                    'method' => $payment->payment_method,
                    'method_label' => ucfirst(str_replace('_', ' ', $payment->payment_method)),
                    'status' => $payment->status,
                    'status_label' => ucfirst(str_replace('_', ' ', $payment->status)),
                    'payment_date' => $payment->payment_date?->toISOString(),
                    'created_at' => $payment->created_at->toISOString(),
                    'created_at_human' => $payment->created_at->diffForHumans()
                ];
            })
            ->toArray();
    }

    /**
     * Get system alerts and notifications
     */
    private function getSystemAlerts(int $limit): array
    {
        $alerts = [];

        // Overdue payments
        $overduePayments = Payment::where('status', 'pending')
            ->where('created_at', '<', now()->subDays(30))
            ->count();
        if ($overduePayments > 0) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'payments',
                'title' => 'Overdue Payments',
                'message' => "{$overduePayments} payments are overdue",
                'count' => $overduePayments,
                'action_url' => '/admin/payments?status=overdue',
                'created_at' => now()->toISOString()
            ];
        }

        // Delayed shipments
        $delayedShipments = Shipment::where('status', 'delayed')->count();
        if ($delayedShipments > 0) {
            $alerts[] = [
                'type' => 'error',
                'category' => 'shipments',
                'title' => 'Delayed Shipments',
                'message' => "{$delayedShipments} shipments are delayed",
                'count' => $delayedShipments,
                'action_url' => '/admin/shipments?status=delayed',
                'created_at' => now()->toISOString()
            ];
        }

        // Pending document verifications
        $pendingDocs = Document::where('status', 'pending')->count();
        if ($pendingDocs > 0) {
            $alerts[] = [
                'type' => 'info',
                'category' => 'documents',
                'title' => 'Pending Document Verifications',
                'message' => "{$pendingDocs} documents require verification",
                'count' => $pendingDocs,
                'action_url' => '/admin/documents?status=pending',
                'created_at' => now()->toISOString()
            ];
        }

        // Expiring documents
        $expiringDocs = Document::where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now())
            ->count();
        if ($expiringDocs > 0) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'documents',
                'title' => 'Expiring Documents',
                'message' => "{$expiringDocs} documents expire within 30 days",
                'count' => $expiringDocs,
                'action_url' => '/admin/documents?expiring=true',
                'created_at' => now()->toISOString()
            ];
        }

        // Pending quotes
        $pendingQuotes = Quote::where('status', 'pending')->count();
        if ($pendingQuotes > 0) {
            $alerts[] = [
                'type' => 'info',
                'category' => 'quotes',
                'title' => 'Pending Quote Approvals',
                'message' => "{$pendingQuotes} quotes require approval",
                'count' => $pendingQuotes,
                'action_url' => '/admin/quotes?status=pending',
                'created_at' => now()->toISOString()
            ];
        }

        return array_slice($alerts, 0, $limit);
    }

    /**
     * Helper methods for calculations
     */
    private function calculateGrowthRate($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return (($current - $previous) / $previous) * 100;
    }

    private function getQuoteConversionRate(): float
    {
        $totalQuotes = Quote::count();
        $convertedQuotes = Quote::where('status', 'converted')->count();
        return $totalQuotes > 0 ? ($convertedQuotes / $totalQuotes) * 100 : 0;
    }

    private function getQuoteApprovalRate(): float
    {
        $totalQuotes = Quote::count();
        $approvedQuotes = Quote::whereIn('status', ['approved', 'converted'])->count();
        return $totalQuotes > 0 ? ($approvedQuotes / $totalQuotes) * 100 : 0;
    }

    private function getOnTimeDeliveryRate(): float
    {
        $deliveredShipments = Shipment::where('status', 'delivered')
            ->whereNotNull('actual_arrival')
            ->whereNotNull('estimated_arrival')
            ->get();

        if ($deliveredShipments->isEmpty()) {
            return 0;
        }

        $onTimeDeliveries = $deliveredShipments->filter(function ($shipment) {
            return $shipment->actual_arrival <= $shipment->estimated_arrival;
        });

        return ($onTimeDeliveries->count() / $deliveredShipments->count()) * 100;
    }

    private function getAverageTransitTime(): float
    {
        return Shipment::where('status', 'delivered')
            ->whereNotNull('departure_date')
            ->whereNotNull('actual_arrival')
            ->get()
            ->avg(function ($shipment) {
                return $shipment->departure_date->diffInDays($shipment->actual_arrival);
            }) ?: 0;
    }

    private function getDelayRate(): float
    {
        $totalShipments = Shipment::count();
        $delayedShipments = Shipment::where('status', 'delayed')->count();
        return $totalShipments > 0 ? ($delayedShipments / $totalShipments) * 100 : 0;
    }

    private function getBookingCompletionRate(): float
    {
        $totalBookings = Booking::count();
        $completedBookings = Booking::where('status', 'delivered')->count();
        return $totalBookings > 0 ? ($completedBookings / $totalBookings) * 100 : 0;
    }

    private function getOutstandingAmount(): float
    {
        return Booking::sum(\DB::raw('total_amount - paid_amount')) ?? 0;
    }

    private function getCollectionRate(): float
    {
        $totalAmount = Booking::sum('total_amount') ?? 0;
        $paidAmount = Booking::sum('paid_amount') ?? 0;
        return $totalAmount > 0 ? ($paidAmount / $totalAmount) * 100 : 0;
    }

    private function getCustomerRetentionRate(): float
    {
        $totalCustomers = Customer::count();
        $repeatCustomers = Customer::has('bookings', '>', 1)->count();
        return $totalCustomers > 0 ? ($repeatCustomers / $totalCustomers) * 100 : 0;
    }

    private function getCustomerChurnRate(): float
    {
        // Customers who haven't made a booking in the last 6 months
        $inactiveCustomers = Customer::whereDoesntHave('bookings', function ($query) {
            $query->where('created_at', '>=', now()->subMonths(6));
        })->count();
        
        $totalCustomers = Customer::count();
        return $totalCustomers > 0 ? ($inactiveCustomers / $totalCustomers) * 100 : 0;
    }

    private function getAverageBookingValue(): float
    {
        return Booking::avg('total_amount') ?? 0;
    }

    private function getCustomerSatisfactionScore(): float
    {
        // This would typically come from a ratings/feedback system
        // For now, return a calculated score based on completion rates and delays
        $completionRate = $this->getBookingCompletionRate();
        $onTimeRate = $this->getOnTimeDeliveryRate();
        return ($completionRate + $onTimeRate) / 2;
    }

    private function getOverdueTasksCount(): int
    {
        // Count various overdue items
        return Payment::where('status', 'pending')
            ->where('created_at', '<', now()->subDays(30))
            ->count() +
            Document::where('status', 'pending')
            ->where('created_at', '<', now()->subDays(7))
            ->count() +
            Quote::where('status', 'pending')
            ->where('created_at', '<', now()->subDays(3))
            ->count();
    }

    private function getSystemHealthScore(): float
    {
        // Calculate based on various system metrics
        $errorRate = $this->getSystemErrorRate();
        $responseTime = $this->getAverageApiResponseTime();
        
        // Simple health score calculation (0-100)
        $healthScore = 100;
        $healthScore -= min($errorRate * 10, 50); // Reduce by error rate
        $healthScore -= min($responseTime / 100, 30); // Reduce by response time
        
        return max($healthScore, 0);
    }

    private function getAverageApiResponseTime(): float
    {
        // This would typically come from monitoring/logging system
        // For now, return a mock value
        return 250; // milliseconds
    }

    private function getSystemErrorRate(): float
    {
        // This would typically come from error logging system
        // For now, return a mock value
        return 0.5; // percentage
    }

    // Additional helper methods for chart data and analytics would go here...
    // These are placeholder implementations for the chart data methods referenced above

    private function getPeriodDates(string $period): array
    {
        return match ($period) {
            'current_month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth()
            ],
            'last_month' => [
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth()
            ],
            'ytd' => [
                'start' => now()->startOfYear(),
                'end' => now()
            ],
            default => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth()
            ]
        };
    }

    private function getTopRevenueRoutes(Carbon $start, Carbon $end): array
    {
        return Payment::join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->join('routes', 'bookings.route_id', '=', 'routes.id')
            ->where('payments.status', 'completed')
            ->whereBetween('payments.payment_date', [$start, $end])
            ->selectRaw('routes.id, routes.origin_country, routes.destination_country, SUM(payments.amount) as total_revenue, COUNT(payments.id) as payment_count')
            ->groupBy('routes.id', 'routes.origin_country', 'routes.destination_country')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->map(function ($route) {
                return [
                    'route_id' => $route->id,
                    'route_name' => $route->origin_country . ' → ' . $route->destination_country,
                    'total_revenue' => $route->total_revenue,
                    'payment_count' => $route->payment_count,
                    'average_revenue' => $route->payment_count > 0 ? $route->total_revenue / $route->payment_count : 0
                ];
            })
            ->toArray();
    }

    private function getPaymentMethodDistribution(Carbon $start, Carbon $end): array
    {
        return Payment::where('status', 'completed')
            ->whereBetween('payment_date', [$start, $end])
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('payment_method')
            ->get()
            ->map(function ($payment) {
                return [
                    'method' => $payment->payment_method,
                    'method_label' => ucfirst(str_replace('_', ' ', $payment->payment_method)),
                    'count' => $payment->count,
                    'total_amount' => $payment->total_amount,
                    'percentage' => 0 // Will be calculated after getting all data
                ];
            })
            ->toArray();
    }

    private function getOutstandingPaymentsSummary(): array
    {
        $outstanding = Booking::selectRaw('
            COUNT(*) as total_bookings,
            SUM(total_amount - paid_amount) as total_outstanding,
            AVG(total_amount - paid_amount) as average_outstanding,
            SUM(CASE WHEN created_at < ? THEN total_amount - paid_amount ELSE 0 END) as overdue_amount
        ', [now()->subDays(30)])
        ->where('paid_amount', '<', \DB::raw('total_amount'))
        ->first();

        return [
            'total_bookings_with_outstanding' => $outstanding->total_bookings ?? 0,
            'total_outstanding_amount' => $outstanding->total_outstanding ?? 0,
            'average_outstanding_amount' => $outstanding->average_outstanding ?? 0,
            'overdue_amount' => $outstanding->overdue_amount ?? 0,
            'collection_priority' => $this->getCollectionPriorityList()
        ];
    }

    private function getRevenueForecast(): array
    {
        // Simple forecast based on historical data and current pipeline
        $lastMonthRevenue = Payment::where('status', 'completed')
            ->whereBetween('payment_date', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('amount') ?? 0;

        $currentMonthRevenue = Payment::where('status', 'completed')
            ->whereBetween('payment_date', [now()->startOfMonth(), now()])
            ->sum('amount') ?? 0;

        $pendingBookingsValue = Booking::whereIn('status', ['confirmed', 'in_transit'])
            ->sum(\DB::raw('total_amount - paid_amount')) ?? 0;

        $projectedMonthlyGrowth = $lastMonthRevenue > 0 ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        return [
            'current_month_actual' => $currentMonthRevenue,
            'current_month_projected' => $currentMonthRevenue + ($pendingBookingsValue * 0.7), // 70% collection rate assumption
            'next_month_forecast' => $lastMonthRevenue * (1 + ($projectedMonthlyGrowth / 100)),
            'pipeline_value' => $pendingBookingsValue,
            'confidence_level' => min(90, max(60, 80 - abs($projectedMonthlyGrowth))) // Dynamic confidence based on volatility
        ];
    }

    private function getPendingActions(): array
    {
        return [
            'quotes_requiring_approval' => Quote::where('status', 'pending')->count(),
            'documents_requiring_verification' => Document::where('status', 'pending')->count(),
            'overdue_payments' => Payment::where('status', 'pending')
                ->where('created_at', '<', now()->subDays(30))->count(),
            'delayed_shipments' => Shipment::where('status', 'delayed')->count(),
            'expiring_documents' => Document::where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>', now())->count(),
            'customer_inquiries' => 0, // Would come from support system
            'system_maintenance_due' => 0 // Would come from maintenance scheduler
        ];
    }

    private function getPerformanceIndicators(): array
    {
        return [
            'quote_response_time' => $this->getAverageQuoteResponseTime(),
            'booking_processing_time' => $this->getAverageBookingProcessingTime(),
            'document_verification_time' => $this->getAverageDocumentVerificationTime(),
            'customer_satisfaction_score' => $this->getCustomerSatisfactionScore(),
            'first_call_resolution_rate' => 85.5, // Mock data - would come from support system
            'system_uptime' => 99.8, // Mock data - would come from monitoring system
            'api_response_time' => $this->getAverageApiResponseTime()
        ];
    }

    private function getCapacityUtilization(): array
    {
        $totalCapacity = 1000; // Mock total monthly capacity
        $currentUtilization = Booking::whereMonth('created_at', now()->month)->count();
        
        return [
            'current_utilization' => $currentUtilization,
            'total_capacity' => $totalCapacity,
            'utilization_percentage' => ($currentUtilization / $totalCapacity) * 100,
            'available_capacity' => $totalCapacity - $currentUtilization,
            'projected_month_end' => $currentUtilization * (now()->daysInMonth / now()->day),
            'capacity_alerts' => $this->getCapacityAlerts($currentUtilization, $totalCapacity)
        ];
    }

    private function getQualityMetrics(): array
    {
        return [
            'booking_accuracy_rate' => $this->getBookingAccuracyRate(),
            'document_rejection_rate' => $this->getDocumentRejectionRate(),
            'shipment_damage_rate' => $this->getShipmentDamageRate(),
            'customer_complaint_rate' => $this->getCustomerComplaintRate(),
            'on_time_delivery_rate' => $this->getOnTimeDeliveryRate(),
            'quote_accuracy_rate' => $this->getQuoteAccuracyRate(),
            'rework_rate' => $this->getReworkRate()
        ];
    }

    private function getRevenueTrendData(string $period): array
    {
        $days = match ($period) {
            '7_days' => 7,
            '30_days' => 30,
            '90_days' => 90,
            default => 30
        };

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenue = Payment::where('status', 'completed')
                ->whereDate('payment_date', $date)
                ->sum('amount') ?? 0;
            
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'date_label' => $date->format('M j'),
                'revenue' => $revenue,
                'cumulative_revenue' => 0 // Will be calculated after loop
            ];
        }

        // Calculate cumulative revenue
        $cumulative = 0;
        foreach ($data as &$item) {
            $cumulative += $item['revenue'];
            $item['cumulative_revenue'] = $cumulative;
        }

        return [
            'period' => $period,
            'data' => $data,
            'total_revenue' => $cumulative,
            'average_daily_revenue' => $cumulative / $days,
            'trend' => $this->calculateTrend($data, 'revenue')
        ];
    }

    private function getBookingStatusDistribution(): array
    {
        $statuses = Booking::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'status_label' => ucfirst(str_replace('_', ' ', $item->status)),
                    'count' => $item->count,
                    'percentage' => 0 // Will be calculated after getting total
                ];
            });

        $total = $statuses->sum('count');
        
        return $statuses->map(function ($item) use ($total) {
            $item['percentage'] = $total > 0 ? ($item['count'] / $total) * 100 : 0;
            return $item;
        })->toArray();
    }

    private function getShipmentProgressData(): array
    {
        $progressData = Shipment::selectRaw('
            status,
            COUNT(*) as count,
            AVG(CASE 
                WHEN status = "preparing" THEN 10
                WHEN status = "in_transit" THEN 50
                WHEN status = "customs" THEN 80
                WHEN status = "delivered" THEN 100
                WHEN status = "delayed" THEN 30
                ELSE 0
            END) as average_progress
        ')
        ->groupBy('status')
        ->get()
        ->map(function ($item) {
            return [
                'status' => $item->status,
                'status_label' => ucfirst(str_replace('_', ' ', $item->status)),
                'count' => $item->count,
                'average_progress' => $item->average_progress ?? 0
            ];
        });

        return [
            'status_distribution' => $progressData->toArray(),
            'overall_progress' => $progressData->avg('average_progress') ?? 0,
            'total_shipments' => $progressData->sum('count')
        ];
    }

    private function getCustomerAcquisitionData(string $period): array
    {
        $days = match ($period) {
            '7_days' => 7,
            '30_days' => 30,
            '90_days' => 90,
            default => 30
        };

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $newCustomers = Customer::whereDate('created_at', $date)->count();
            
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'date_label' => $date->format('M j'),
                'new_customers' => $newCustomers,
                'cumulative_customers' => 0 // Will be calculated after loop
            ];
        }

        // Calculate cumulative customers
        $cumulative = Customer::where('created_at', '<', now()->subDays($days))->count();
        foreach ($data as &$item) {
            $cumulative += $item['new_customers'];
            $item['cumulative_customers'] = $cumulative;
        }

        return [
            'period' => $period,
            'data' => $data,
            'total_new_customers' => array_sum(array_column($data, 'new_customers')),
            'average_daily_acquisition' => array_sum(array_column($data, 'new_customers')) / $days,
            'growth_rate' => $this->calculateTrend($data, 'new_customers')
        ];
    }

    private function getRoutePerformanceData(): array
    {
        return Booking::join('routes', 'bookings.route_id', '=', 'routes.id')
            ->selectRaw('
                routes.id,
                routes.origin_country,
                routes.destination_country,
                COUNT(bookings.id) as total_bookings,
                AVG(bookings.total_amount) as average_value,
                SUM(CASE WHEN bookings.status = "delivered" THEN 1 ELSE 0 END) as completed_bookings,
                AVG(CASE WHEN shipments.actual_arrival IS NOT NULL AND shipments.estimated_arrival IS NOT NULL 
                    THEN DATEDIFF(shipments.actual_arrival, shipments.estimated_arrival) ELSE NULL END) as avg_delay_days
            ')
            ->leftJoin('shipments', 'bookings.id', '=', 'shipments.booking_id')
            ->groupBy('routes.id', 'routes.origin_country', 'routes.destination_country')
            ->orderByDesc('total_bookings')
            ->limit(10)
            ->get()
            ->map(function ($route) {
                return [
                    'route_id' => $route->id,
                    'route_name' => $route->origin_country . ' → ' . $route->destination_country,
                    'total_bookings' => $route->total_bookings,
                    'average_value' => $route->average_value ?? 0,
                    'completion_rate' => $route->total_bookings > 0 ? ($route->completed_bookings / $route->total_bookings) * 100 : 0,
                    'average_delay_days' => $route->avg_delay_days ?? 0,
                    'performance_score' => $this->calculateRoutePerformanceScore($route)
                ];
            })
            ->toArray();
    }

    private function getConversionFunnelData(string $period): array
    {
        $days = match ($period) {
            '7_days' => 7,
            '30_days' => 30,
            '90_days' => 90,
            default => 30
        };

        $startDate = now()->subDays($days);
        
        $quotes = Quote::where('created_at', '>=', $startDate)->count();
        $approvedQuotes = Quote::where('created_at', '>=', $startDate)
            ->whereIn('status', ['approved', 'converted'])->count();
        $convertedQuotes = Quote::where('created_at', '>=', $startDate)
            ->where('status', 'converted')->count();
        $completedBookings = Booking::where('created_at', '>=', $startDate)
            ->where('status', 'delivered')->count();

        return [
            'period' => $period,
            'funnel_stages' => [
                [
                    'stage' => 'quotes_requested',
                    'stage_label' => 'Quotes Requested',
                    'count' => $quotes,
                    'conversion_rate' => 100,
                    'drop_off_rate' => 0
                ],
                [
                    'stage' => 'quotes_approved',
                    'stage_label' => 'Quotes Approved',
                    'count' => $approvedQuotes,
                    'conversion_rate' => $quotes > 0 ? ($approvedQuotes / $quotes) * 100 : 0,
                    'drop_off_rate' => $quotes > 0 ? (($quotes - $approvedQuotes) / $quotes) * 100 : 0
                ],
                [
                    'stage' => 'bookings_created',
                    'stage_label' => 'Bookings Created',
                    'count' => $convertedQuotes,
                    'conversion_rate' => $approvedQuotes > 0 ? ($convertedQuotes / $approvedQuotes) * 100 : 0,
                    'drop_off_rate' => $approvedQuotes > 0 ? (($approvedQuotes - $convertedQuotes) / $approvedQuotes) * 100 : 0
                ],
                [
                    'stage' => 'bookings_completed',
                    'stage_label' => 'Bookings Completed',
                    'count' => $completedBookings,
                    'conversion_rate' => $convertedQuotes > 0 ? ($completedBookings / $convertedQuotes) * 100 : 0,
                    'drop_off_rate' => $convertedQuotes > 0 ? (($convertedQuotes - $completedBookings) / $convertedQuotes) * 100 : 0
                ]
            ],
        ];
    }

    // Additional helper methods for comprehensive dashboard functionality
    
    private function getCollectionPriorityList(): array
    {
        return Booking::selectRaw('
            id, booking_reference, customer_id,
            (total_amount - paid_amount) as outstanding_amount,
            DATEDIFF(NOW(), created_at) as days_outstanding
        ')
        ->where('paid_amount', '<', \DB::raw('total_amount'))
        ->orderByDesc(\DB::raw('(total_amount - paid_amount) * DATEDIFF(NOW(), created_at)'))
        ->limit(10)
        ->get()
        ->map(function ($booking) {
            return [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'outstanding_amount' => $booking->outstanding_amount,
                'days_outstanding' => $booking->days_outstanding,
                'priority_score' => $booking->outstanding_amount * $booking->days_outstanding
            ];
        })
        ->toArray();
    }

    private function getAverageQuoteResponseTime(): float
    {
        // Mock implementation - would calculate based on quote creation to approval time
        return 24.5; // hours
    }

    private function getAverageBookingProcessingTime(): float
    {
        // Mock implementation - would calculate based on booking creation to confirmation time
        return 4.2; // hours
    }

    private function getAverageDocumentVerificationTime(): float
    {
        return Document::whereNotNull('verified_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, verified_at)) as avg_hours')
            ->value('avg_hours') ?? 48.0;
    }

    private function getCapacityAlerts(int $current, int $total): array
    {
        $utilizationRate = ($current / $total) * 100;
        $alerts = [];

        if ($utilizationRate > 90) {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'Capacity utilization is above 90%',
                'recommendation' => 'Consider increasing capacity or deferring non-urgent bookings'
            ];
        } elseif ($utilizationRate > 75) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Capacity utilization is above 75%',
                'recommendation' => 'Monitor closely and prepare for capacity expansion'
            ];
        }

        return $alerts;
    }

    private function getBookingAccuracyRate(): float
    {
        // Mock implementation - would calculate based on booking modifications/corrections
        return 94.5;
    }

    private function getDocumentRejectionRate(): float
    {
        $totalDocuments = Document::count();
        $rejectedDocuments = Document::where('status', 'rejected')->count();
        return $totalDocuments > 0 ? ($rejectedDocuments / $totalDocuments) * 100 : 0;
    }

    private function getShipmentDamageRate(): float
    {
        // Mock implementation - would come from damage reports
        return 0.8;
    }

    private function getCustomerComplaintRate(): float
    {
        // Mock implementation - would come from support system
        return 2.1;
    }

    private function getQuoteAccuracyRate(): float
    {
        // Mock implementation - would calculate based on quote vs final booking amount variance
        return 91.2;
    }

    private function getReworkRate(): float
    {
        // Mock implementation - would calculate based on tasks that needed to be redone
        return 3.4;
    }

    private function calculateTrend(array $data, string $field): string
    {
        if (count($data) < 2) return 'stable';
        
        $firstHalf = array_slice($data, 0, count($data) / 2);
        $secondHalf = array_slice($data, count($data) / 2);
        
        $firstAvg = array_sum(array_column($firstHalf, $field)) / count($firstHalf);
        $secondAvg = array_sum(array_column($secondHalf, $field)) / count($secondHalf);
        
        $change = (($secondAvg - $firstAvg) / max($firstAvg, 1)) * 100;
        
        if ($change > 5) return 'increasing';
        if ($change < -5) return 'decreasing';
        return 'stable';
    }

    private function calculateRoutePerformanceScore($route): float
    {
        $completionWeight = 0.4;
        $valueWeight = 0.3;
        $timelinessWeight = 0.3;
        
        $completionScore = min($route->completion_rate ?? 0, 100);
        $valueScore = min(($route->average_value ?? 0) / 5000 * 100, 100); // Normalize to $5000 max
        $timelinessScore = max(100 - abs($route->avg_delay_days ?? 0) * 10, 0);
        
        return ($completionScore * $completionWeight) + 
               ($valueScore * $valueWeight) + 
               ($timelinessScore * $timelinessWeight);
    }
}