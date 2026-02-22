<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Advanced Analytics Service
 * 
 * Provides comprehensive analytics and reporting capabilities
 * including trend analysis, performance metrics, and business intelligence
 */
class AdvancedAnalyticsService
{
    /**
     * Get comprehensive dashboard analytics
     */
    public function getDashboardAnalytics(): array
    {
        return Cache::remember('dashboard.analytics', 300, function () {
            return [
                'overview' => $this->getOverviewMetrics(),
                'trends' => $this->getTrendAnalysis(),
                'performance' => $this->getPerformanceMetrics(),
                'revenue' => $this->getRevenueAnalytics(),
                'customer_insights' => $this->getCustomerInsights(),
                'operational' => $this->getOperationalMetrics(),
                'generated_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get overview metrics with comparisons
     */
    public function getOverviewMetrics(): array
    {
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();
        $currentYear = now()->startOfYear();

        return [
            'quotes' => [
                'total' => Quote::count(),
                'this_month' => Quote::where('created_at', '>=', $currentMonth)->count(),
                'last_month' => Quote::whereBetween('created_at', [$previousMonth, $currentMonth])->count(),
                'this_year' => Quote::where('created_at', '>=', $currentYear)->count(),
                'pending' => Quote::where('status', 'pending')->count(),
                'approved' => Quote::where('status', 'approved')->count(),
                'conversion_rate' => $this->calculateQuoteConversionRate(),
            ],
            'bookings' => [
                'total' => Booking::count(),
                'this_month' => Booking::where('created_at', '>=', $currentMonth)->count(),
                'last_month' => Booking::whereBetween('created_at', [$previousMonth, $currentMonth])->count(),
                'this_year' => Booking::where('created_at', '>=', $currentYear)->count(),
                'active' => Booking::whereIn('status', ['confirmed', 'in_transit'])->count(),
                'completed' => Booking::where('status', 'delivered')->count(),
                'completion_rate' => $this->calculateBookingCompletionRate(),
            ],
            'customers' => [
                'total' => Customer::count(),
                'new_this_month' => Customer::where('created_at', '>=', $currentMonth)->count(),
                'active' => Customer::where('is_active', true)->count(),
                'verified' => Customer::where('is_verified', true)->count(),
                'retention_rate' => $this->calculateCustomerRetentionRate(),
            ],
            'revenue' => [
                'total' => Payment::where('status', 'completed')->sum('amount'),
                'this_month' => Payment::where('status', 'completed')
                    ->where('payment_date', '>=', $currentMonth)
                    ->sum('amount'),
                'this_year' => Payment::where('status', 'completed')
                    ->where('payment_date', '>=', $currentYear)
                    ->sum('amount'),
                'average_order_value' => $this->calculateAverageOrderValue(),
            ],
        ];
    }

    /**
     * Get trend analysis for the last 12 months
     */
    public function getTrendAnalysis(): array
    {
        $months = collect(range(11, 0))->map(function ($monthsAgo) {
            return now()->subMonths($monthsAgo)->startOfMonth();
        });

        return [
            'quotes_trend' => $months->map(function ($month) {
                return [
                    'month' => $month->format('Y-m'),
                    'label' => $month->format('M Y'),
                    'count' => Quote::whereBetween('created_at', [
                        $month,
                        $month->copy()->endOfMonth()
                    ])->count(),
                ];
            })->values(),
            'bookings_trend' => $months->map(function ($month) {
                return [
                    'month' => $month->format('Y-m'),
                    'label' => $month->format('M Y'),
                    'count' => Booking::whereBetween('created_at', [
                        $month,
                        $month->copy()->endOfMonth()
                    ])->count(),
                ];
            })->values(),
            'revenue_trend' => $months->map(function ($month) {
                return [
                    'month' => $month->format('Y-m'),
                    'label' => $month->format('M Y'),
                    'amount' => Payment::where('status', 'completed')
                        ->whereBetween('payment_date', [
                            $month,
                            $month->copy()->endOfMonth()
                        ])->sum('amount'),
                ];
            })->values(),
            'customer_acquisition_trend' => $months->map(function ($month) {
                return [
                    'month' => $month->format('Y-m'),
                    'label' => $month->format('M Y'),
                    'count' => Customer::whereBetween('created_at', [
                        $month,
                        $month->copy()->endOfMonth()
                    ])->count(),
                ];
            })->values(),
        ];
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'quote_performance' => [
                'average_response_time' => $this->calculateAverageQuoteResponseTime(),
                'approval_rate' => $this->calculateQuoteApprovalRate(),
                'conversion_rate' => $this->calculateQuoteConversionRate(),
                'expiry_rate' => $this->calculateQuoteExpiryRate(),
            ],
            'booking_performance' => [
                'completion_rate' => $this->calculateBookingCompletionRate(),
                'average_delivery_time' => $this->calculateAverageDeliveryTime(),
                'on_time_delivery_rate' => $this->calculateOnTimeDeliveryRate(),
                'cancellation_rate' => $this->calculateBookingCancellationRate(),
            ],
            'payment_performance' => [
                'success_rate' => $this->calculatePaymentSuccessRate(),
                'average_processing_time' => $this->calculateAveragePaymentProcessingTime(),
                'refund_rate' => $this->calculateRefundRate(),
            ],
            'document_performance' => [
                'verification_rate' => $this->calculateDocumentVerificationRate(),
                'average_verification_time' => $this->calculateAverageDocumentVerificationTime(),
                'rejection_rate' => $this->calculateDocumentRejectionRate(),
            ],
        ];
    }

    /**
     * Get detailed revenue analytics
     */
    public function getRevenueAnalytics(): array
    {
        return [
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'revenue_by_month' => $this->getRevenueByMonth(),
            'revenue_by_payment_method' => $this->getRevenueByPaymentMethod(),
            'revenue_by_route' => $this->getRevenueByRoute(),
            'average_order_value' => $this->calculateAverageOrderValue(),
            'revenue_growth_rate' => $this->calculateRevenueGrowthRate(),
            'top_customers_by_revenue' => $this->getTopCustomersByRevenue(),
        ];
    }

    /**
     * Get customer insights
     */
    public function getCustomerInsights(): array
    {
        return [
            'total_customers' => Customer::count(),
            'customer_segments' => $this->getCustomerSegments(),
            'customer_lifetime_value' => $this->calculateCustomerLifetimeValue(),
            'repeat_customer_rate' => $this->calculateRepeatCustomerRate(),
            'customer_acquisition_cost' => $this->calculateCustomerAcquisitionCost(),
            'top_customers' => $this->getTopCustomers(),
            'customer_geography' => $this->getCustomerGeography(),
        ];
    }

    /**
     * Get operational metrics
     */
    public function getOperationalMetrics(): array
    {
        return [
            'shipment_metrics' => [
                'total_shipments' => Shipment::count(),
                'in_transit' => Shipment::where('status', 'in_transit')->count(),
                'delivered' => Shipment::where('status', 'delivered')->count(),
                'delayed' => Shipment::where('status', 'delayed')->count(),
                'average_transit_time' => $this->calculateAverageTransitTime(),
            ],
            'document_metrics' => [
                'total_documents' => Document::count(),
                'pending_verification' => Document::where('status', 'pending')->count(),
                'verified' => Document::where('status', 'approved')->count(),
                'rejected' => Document::where('status', 'rejected')->count(),
                'expiring_soon' => Document::where('expiry_date', '<=', now()->addDays(30))
                    ->where('status', 'approved')->count(),
            ],
            'efficiency_metrics' => [
                'quote_to_booking_time' => $this->calculateQuoteToBookingTime(),
                'booking_to_shipment_time' => $this->calculateBookingToShipmentTime(),
                'document_processing_time' => $this->calculateDocumentProcessingTime(),
            ],
        ];
    }

    // Helper calculation methods

    private function calculateQuoteConversionRate(): float
    {
        $totalQuotes = Quote::count();
        if ($totalQuotes === 0) return 0;
        
        $convertedQuotes = Quote::where('status', 'converted')->count();
        return round(($convertedQuotes / $totalQuotes) * 100, 2);
    }

    private function calculateQuoteApprovalRate(): float
    {
        $totalQuotes = Quote::count();
        if ($totalQuotes === 0) return 0;
        
        $approvedQuotes = Quote::where('status', 'approved')->count();
        return round(($approvedQuotes / $totalQuotes) * 100, 2);
    }

    private function calculateQuoteExpiryRate(): float
    {
        $totalQuotes = Quote::count();
        if ($totalQuotes === 0) return 0;
        
        $expiredQuotes = Quote::where('valid_until', '<', now())->count();
        return round(($expiredQuotes / $totalQuotes) * 100, 2);
    }

    private function calculateBookingCompletionRate(): float
    {
        $totalBookings = Booking::count();
        if ($totalBookings === 0) return 0;
        
        $completedBookings = Booking::where('status', 'delivered')->count();
        return round(($completedBookings / $totalBookings) * 100, 2);
    }

    private function calculateBookingCancellationRate(): float
    {
        $totalBookings = Booking::count();
        if ($totalBookings === 0) return 0;
        
        $cancelledBookings = Booking::where('status', 'cancelled')->count();
        return round(($cancelledBookings / $totalBookings) * 100, 2);
    }

    private function calculatePaymentSuccessRate(): float
    {
        $totalPayments = Payment::count();
        if ($totalPayments === 0) return 0;
        
        $successfulPayments = Payment::where('status', 'completed')->count();
        return round(($successfulPayments / $totalPayments) * 100, 2);
    }

    private function calculateRefundRate(): float
    {
        $totalPayments = Payment::where('status', 'completed')->count();
        if ($totalPayments === 0) return 0;
        
        $refundedPayments = Payment::where('status', 'refunded')->count();
        return round(($refundedPayments / $totalPayments) * 100, 2);
    }

    private function calculateDocumentVerificationRate(): float
    {
        $totalDocuments = Document::count();
        if ($totalDocuments === 0) return 0;
        
        $verifiedDocuments = Document::where('status', 'approved')->count();
        return round(($verifiedDocuments / $totalDocuments) * 100, 2);
    }

    private function calculateDocumentRejectionRate(): float
    {
        $totalDocuments = Document::count();
        if ($totalDocuments === 0) return 0;
        
        $rejectedDocuments = Document::where('status', 'rejected')->count();
        return round(($rejectedDocuments / $totalDocuments) * 100, 2);
    }

    private function calculateAverageOrderValue(): float
    {
        return Payment::where('status', 'completed')->avg('amount') ?? 0;
    }

    private function calculateCustomerRetentionRate(): float
    {
        $totalCustomers = Customer::count();
        if ($totalCustomers === 0) return 0;
        
        $repeatCustomers = Customer::has('bookings', '>', 1)->count();
        return round(($repeatCustomers / $totalCustomers) * 100, 2);
    }

    private function calculateRepeatCustomerRate(): float
    {
        return $this->calculateCustomerRetentionRate();
    }

    private function calculateCustomerLifetimeValue(): float
    {
        return Customer::withCount('bookings')
            ->withSum('payments', 'amount')
            ->get()
            ->avg('payments_sum_amount') ?? 0;
    }

    private function calculateCustomerAcquisitionCost(): float
    {
        // This would typically include marketing costs, but for now return 0
        return 0;
    }

    private function calculateAverageQuoteResponseTime(): float
    {
        // Calculate average time from quote creation to approval
        $quotes = Quote::whereNotNull('approved_at')->get();
        if ($quotes->isEmpty()) return 0;
        
        $totalHours = $quotes->sum(function ($quote) {
            return $quote->created_at->diffInHours($quote->approved_at);
        });
        
        return round($totalHours / $quotes->count(), 2);
    }

    private function calculateAverageDeliveryTime(): float
    {
        $bookings = Booking::whereNotNull('delivery_date')->get();
        if ($bookings->isEmpty()) return 0;
        
        $totalDays = $bookings->sum(function ($booking) {
            return $booking->created_at->diffInDays($booking->delivery_date);
        });
        
        return round($totalDays / $bookings->count(), 2);
    }

    private function calculateOnTimeDeliveryRate(): float
    {
        $bookingsWithEstimate = Booking::whereNotNull('estimated_delivery')
            ->whereNotNull('delivery_date')->count();
        
        if ($bookingsWithEstimate === 0) return 0;
        
        $onTimeDeliveries = Booking::whereNotNull('estimated_delivery')
            ->whereNotNull('delivery_date')
            ->whereRaw('delivery_date <= estimated_delivery')
            ->count();
        
        return round(($onTimeDeliveries / $bookingsWithEstimate) * 100, 2);
    }

    private function calculateAveragePaymentProcessingTime(): float
    {
        $payments = Payment::where('status', 'completed')
            ->whereNotNull('payment_date')->get();
        
        if ($payments->isEmpty()) return 0;
        
        $totalHours = $payments->sum(function ($payment) {
            return $payment->created_at->diffInHours($payment->payment_date);
        });
        
        return round($totalHours / $payments->count(), 2);
    }

    private function calculateAverageDocumentVerificationTime(): float
    {
        $documents = Document::where('status', 'approved')
            ->whereNotNull('verified_at')->get();
        
        if ($documents->isEmpty()) return 0;
        
        $totalHours = $documents->sum(function ($document) {
            return $document->created_at->diffInHours($document->verified_at);
        });
        
        return round($totalHours / $documents->count(), 2);
    }

    private function calculateAverageTransitTime(): float
    {
        $shipments = Shipment::where('status', 'delivered')
            ->whereNotNull('departure_date')
            ->whereNotNull('actual_arrival')->get();
        
        if ($shipments->isEmpty()) return 0;
        
        $totalDays = $shipments->sum(function ($shipment) {
            return $shipment->departure_date->diffInDays($shipment->actual_arrival);
        });
        
        return round($totalDays / $shipments->count(), 2);
    }

    private function calculateQuoteToBookingTime(): float
    {
        $bookings = Booking::whereHas('quote')->with('quote')->get();
        if ($bookings->isEmpty()) return 0;
        
        $totalHours = $bookings->sum(function ($booking) {
            return $booking->quote->created_at->diffInHours($booking->created_at);
        });
        
        return round($totalHours / $bookings->count(), 2);
    }

    private function calculateBookingToShipmentTime(): float
    {
        $shipments = Shipment::whereHas('booking')->with('booking')->get();
        if ($shipments->isEmpty()) return 0;
        
        $totalHours = $shipments->sum(function ($shipment) {
            return $shipment->booking->created_at->diffInHours($shipment->created_at);
        });
        
        return round($totalHours / $shipments->count(), 2);
    }

    private function calculateDocumentProcessingTime(): float
    {
        return $this->calculateAverageDocumentVerificationTime();
    }

    private function calculateRevenueGrowthRate(): float
    {
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();
        
        $currentRevenue = Payment::where('status', 'completed')
            ->where('payment_date', '>=', $currentMonth)
            ->sum('amount');
        
        $previousRevenue = Payment::where('status', 'completed')
            ->whereBetween('payment_date', [$previousMonth, $currentMonth])
            ->sum('amount');
        
        if ($previousRevenue == 0) return 0;
        
        return round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2);
    }

    private function getRevenueByMonth(): array
    {
        return Payment::where('status', 'completed')
            ->selectRaw('DATE_FORMAT(payment_date, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get()
            ->toArray();
    }

    private function getRevenueByPaymentMethod(): array
    {
        return Payment::where('status', 'completed')
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->orderBy('total', 'desc')
            ->get()
            ->toArray();
    }

    private function getRevenueByRoute(): array
    {
        return Booking::join('payments', 'bookings.id', '=', 'payments.booking_id')
            ->join('routes', 'bookings.route_id', '=', 'routes.id')
            ->where('payments.status', 'completed')
            ->selectRaw('routes.origin_country, routes.destination_country, SUM(payments.amount) as total')
            ->groupBy('routes.origin_country', 'routes.destination_country')
            ->orderBy('total', 'desc')
            ->get()
            ->toArray();
    }

    private function getTopCustomersByRevenue(): array
    {
        return Customer::join('payments', 'customers.id', '=', 'payments.customer_id')
            ->where('payments.status', 'completed')
            ->selectRaw('customers.id, customers.first_name, customers.last_name, customers.email, SUM(payments.amount) as total_spent, COUNT(payments.id) as payment_count')
            ->groupBy('customers.id', 'customers.first_name', 'customers.last_name', 'customers.email')
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getTopCustomers(): array
    {
        return $this->getTopCustomersByRevenue();
    }

    private function getCustomerSegments(): array
    {
        return [
            'new' => Customer::where('created_at', '>=', now()->subDays(30))->count(),
            'active' => Customer::whereHas('bookings', function ($query) {
                $query->where('created_at', '>=', now()->subDays(90));
            })->count(),
            'inactive' => Customer::whereDoesntHave('bookings', function ($query) {
                $query->where('created_at', '>=', now()->subDays(90));
            })->count(),
            'high_value' => Customer::whereHas('payments', function ($query) {
                $query->where('status', 'completed')
                    ->havingRaw('SUM(amount) > 5000');
            })->count(),
        ];
    }

    private function getCustomerGeography(): array
    {
        return Customer::selectRaw('country, COUNT(*) as count')
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}