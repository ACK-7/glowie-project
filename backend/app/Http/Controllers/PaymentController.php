<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseApiController;
use App\Services\PaymentService;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Http\Requests\PaymentRequest;
use App\Http\Requests\RefundRequest;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Payment Controller
 * 
 * Handles payment management with comprehensive transaction display,
 * overdue payment detection, and refund processing with eligibility validation.
 * 
 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5
 */
class PaymentController extends BaseApiController
{
    public function __construct(
        private PaymentService $paymentService,
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    /**
     * Display a listing of payments with filtering options
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // If accessed by authenticated customer, filter by their ID
            $user = $request->user();
            if ($user && $user instanceof \App\Models\Customer) {
                $filters = ['customer_id' => $user->id];
                
                $payments = $this->paymentRepository->getFilteredPaginated(
                    $filters,
                    100, // Get all customer payments
                    ['booking.customer', 'customer', 'booking.vehicle'],
                    'created_at',
                    'desc'
                );
                
                return $this->successResponse([
                    'data' => $payments->items(),
                    'meta' => [
                        'total' => $payments->total(),
                        'customer_id' => $user->id
                    ]
                ], 'Payments retrieved successfully');
            }
            
            // For admin users, use full filtering
            $filters = $request->only([
                'status', 'payment_method', 'customer_id', 'booking_id',
                'amount_min', 'amount_max', 'overdue', 'currency',
                'payment_gateway', 'search'
            ]);
            
            $perPage = $request->get('per_page', 15);
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $payments = $this->paymentRepository->getFilteredPaginated(
                $filters,
                $perPage,
                ['booking.customer', 'customer', 'booking.vehicle'],
                $sortBy,
                $sortOrder
            );
            
            return $this->paginatedResponse($payments, 'Payments retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve payments', [
                'error' => $e->getMessage(),
                'filters' => $filters ?? []
            ]);
            
            return $this->errorResponse('Failed to retrieve payments', 500);
        }
    }

    /**
     * Create a new payment
     * 
     * @param PaymentRequest $request
     * @return JsonResponse
     */
    public function store(PaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->createPayment($request->validated());
            
            return $this->successResponse(
                $payment->load(['booking.customer', 'customer']),
                'Payment created successfully',
                201
            );
            
        } catch (Exception $e) {
            Log::error('Failed to create payment', [
                'error' => $e->getMessage(),
                'data' => $request->validated()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Display the specified payment with comprehensive details
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $payment = $this->paymentRepository->findWithRelations($id, [
                'booking.customer', 'customer', 'booking.vehicle', 'booking.route'
            ]);
            
            if (!$payment) {
                return $this->errorResponse('Payment not found', 404);
            }
            
            // Get comprehensive payment information
            $paymentData = [
                'payment' => $payment,
                'payment_info' => [
                    'status_label' => $payment->status_label,
                    'method_label' => $payment->method_label,
                    'formatted_amount' => $payment->formatted_amount,
                    'is_overdue' => $payment->is_overdue,
                    'days_overdue' => $payment->days_overdue,
                    'is_refundable' => $payment->is_refundable,
                    'processing_time' => $payment->processing_time,
                ],
                'fees_breakdown' => $payment->calculateFees(),
                'payment_instructions' => $payment->getPaymentInstructions(),
                'booking_payment_status' => [
                    'total_amount' => $payment->booking->total_amount,
                    'paid_amount' => $payment->booking->paid_amount,
                    'remaining_amount' => $payment->booking->total_amount - $payment->booking->paid_amount,
                    'is_fully_paid' => $payment->booking->paid_amount >= $payment->booking->total_amount,
                ]
            ];
            
            return $this->successResponse($paymentData, 'Payment details retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve payment details', [
                'payment_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve payment details', 500);
        }
    }

    /**
     * Update the specified payment
     * 
     * @param PaymentRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(PaymentRequest $request, int $id): JsonResponse
    {
        try {
            $payment = $this->paymentRepository->update($id, $request->validated());
            
            return $this->successResponse(
                $payment->load(['booking.customer', 'customer']),
                'Payment updated successfully'
            );
            
        } catch (Exception $e) {
            Log::error('Failed to update payment', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->validated()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Complete a payment
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'nullable|string|max:100',
            'metadata' => 'nullable|array'
        ]);
        
        try {
            $payment = $this->paymentService->completePayment(
                $id,
                $request->transaction_id,
                $request->metadata ?? []
            );
            
            return $this->successResponse(
                $payment->load(['booking.customer']),
                'Payment completed successfully'
            );
            
        } catch (Exception $e) {
            Log::error('Failed to complete payment', [
                'payment_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Process a refund for the payment
     * 
     * @param RefundRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function refund(RefundRequest $request, int $id): JsonResponse
    {
        try {
            $refundPayment = $this->paymentService->processRefund(
                $id,
                $request->refund_amount,
                $request->reason
            );
            
            return $this->successResponse(
                $refundPayment->load(['booking.customer']),
                'Refund processed successfully'
            );
            
        } catch (Exception $e) {
            Log::error('Failed to process refund', [
                'payment_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get payments requiring attention (overdue, failed, etc.)
     * 
     * @return JsonResponse
     */
    public function requiresAttention(): JsonResponse
    {
        try {
            $payments = $this->paymentService->getPaymentsRequiringAttention();
            
            return $this->successResponse($payments, 'Payments requiring attention retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve payments requiring attention', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve payments requiring attention', 500);
        }
    }

    /**
     * Get overdue payments with collection action suggestions
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function overdue(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365'
        ]);
        
        try {
            $days = $request->get('days', 30);
            $overduePayments = $this->paymentRepository->getOverdue($days);
            
            // Add collection action suggestions
            $paymentsWithActions = $overduePayments->map(function ($payment) {
                $daysOverdue = $payment->days_overdue;
                $actions = [];
                
                if ($daysOverdue <= 7) {
                    $actions = ['Send gentle reminder', 'Contact customer via phone'];
                } elseif ($daysOverdue <= 14) {
                    $actions = ['Send urgent reminder', 'Schedule follow-up call', 'Offer payment plan'];
                } elseif ($daysOverdue <= 30) {
                    $actions = ['Escalate to management', 'Consider legal action', 'Suspend services'];
                } else {
                    $actions = ['Legal collection process', 'Write-off consideration', 'Debt collection agency'];
                }
                
                $payment->suggested_actions = $actions;
                $payment->priority = $daysOverdue > 30 ? 'high' : ($daysOverdue > 14 ? 'medium' : 'low');
                
                return $payment;
            });
            
            return $this->successResponse([
                'overdue_payments' => $paymentsWithActions,
                'total_overdue' => $paymentsWithActions->count(),
                'total_overdue_amount' => $paymentsWithActions->sum('amount'),
                'high_priority_count' => $paymentsWithActions->where('priority', 'high')->count(),
                'medium_priority_count' => $paymentsWithActions->where('priority', 'medium')->count(),
                'low_priority_count' => $paymentsWithActions->where('priority', 'low')->count(),
            ], 'Overdue payments retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve overdue payments', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve overdue payments', 500);
        }
    }

    /**
     * Process overdue payments automatically
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function processOverdue(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365'
        ]);
        
        try {
            $days = $request->get('days', 30);
            $results = $this->paymentService->processOverduePayments($days);
            
            return $this->successResponse($results, 'Overdue payments processed successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to process overdue payments', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to process overdue payments', 500);
        }
    }

    /**
     * Get payment statistics and financial analytics
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            Log::info('Payment statistics endpoint called');
            
            // Simple test - just return basic counts
            $totalPayments = Payment::count();
            $completedPayments = Payment::where('status', 'completed')->count();
            $pendingPayments = Payment::where('status', 'pending')->count();
            $totalRevenue = Payment::where('status', 'completed')->sum('amount');
            
            $statistics = [
                'total_payments' => $totalPayments,
                'completed_payments' => $completedPayments,
                'pending_payments' => $pendingPayments,
                'failed_payments' => Payment::where('status', 'failed')->count(),
                'refunded_payments' => Payment::where('status', 'refunded')->count(),
                'cancelled_payments' => Payment::where('status', 'cancelled')->count(),
                'overdue_payments' => Payment::where('status', 'pending')->where('created_at', '<', now()->subDays(30))->count(),
                'total_revenue' => $totalRevenue,
                'success_rate' => $totalPayments > 0 ? ($completedPayments / $totalPayments) * 100 : 0,
            ];
            
            Log::info('Payment statistics retrieved', ['stats' => $statistics]);
            
            return $this->successResponse($statistics, 'Payment statistics retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve payment statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to retrieve payment statistics', 500);
        }
    }

    /**
     * Get revenue analytics with time period comparisons
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function revenueAnalytics(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:daily,weekly,monthly,yearly'
        ]);
        
        try {
            $startDate = $request->start_date ? \Carbon\Carbon::parse($request->start_date) : now()->subMonth();
            $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date) : now();
            $period = $request->get('period', 'daily');
            
            $currentRevenue = $this->paymentRepository->getTotalRevenue($startDate, $endDate);
            
            // Calculate previous period for comparison
            $periodDiff = $startDate->diffInDays($endDate);
            $previousStart = $startDate->copy()->subDays($periodDiff);
            $previousEnd = $startDate->copy()->subDay();
            $previousRevenue = $this->paymentRepository->getTotalRevenue($previousStart, $previousEnd);
            
            $revenueChange = $previousRevenue > 0 ? 
                (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
            
            $analytics = [
                'current_period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_revenue' => $currentRevenue,
                    'revenue_by_method' => $this->paymentRepository->getRevenueByMethod($startDate, $endDate),
                ],
                'previous_period' => [
                    'start_date' => $previousStart,
                    'end_date' => $previousEnd,
                    'total_revenue' => $previousRevenue,
                ],
                'comparison' => [
                    'revenue_change' => $revenueChange,
                    'revenue_change_amount' => $currentRevenue - $previousRevenue,
                    'trend' => $revenueChange > 0 ? 'up' : ($revenueChange < 0 ? 'down' : 'stable'),
                ],
                'trends' => $this->paymentRepository->getRevenueTrends($periodDiff),
            ];
            
            return $this->successResponse($analytics, 'Revenue analytics retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve revenue analytics', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve revenue analytics', 500);
        }
    }

    /**
     * Calculate payment fees
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateFees(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:' . implode(',', \App\Models\Payment::VALID_METHODS)
        ]);
        
        try {
            $fees = $this->paymentService->calculatePaymentFees(
                $request->amount,
                $request->payment_method
            );
            
            return $this->successResponse($fees, 'Payment fees calculated successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to calculate payment fees', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to calculate payment fees', 500);
        }
    }

    /**
     * Get payment instructions
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function instructions(int $id): JsonResponse
    {
        try {
            $instructions = $this->paymentService->getPaymentInstructions($id);
            
            return $this->successResponse($instructions, 'Payment instructions retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve payment instructions', [
                'payment_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve payment instructions', 500);
        }
    }

    /**
     * Search payments across multiple fields
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100'
        ]);
        
        try {
            $payments = $this->paymentService->searchPayments($request->query);
            
            return $this->successResponse($payments, 'Search results retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to search payments', [
                'query' => $request->query,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to search payments', 500);
        }
    }

    /**
     * Get recent payments
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $payments = $this->paymentRepository->getRecent($limit);
            
            return $this->successResponse($payments, 'Recent payments retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve recent payments', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve recent payments', 500);
        }
    }

    /**
     * Get payments by booking
     * 
     * @param int $bookingId
     * @return JsonResponse
     */
    public function byBooking(int $bookingId): JsonResponse
    {
        try {
            $payments = $this->paymentRepository->getByBooking($bookingId);
            $booking = \App\Models\Booking::findOrFail($bookingId);
            
            return $this->successResponse([
                'payments' => $payments,
                'booking_summary' => [
                    'total_amount' => $booking->total_amount,
                    'paid_amount' => $booking->paid_amount,
                    'remaining_amount' => $booking->total_amount - $booking->paid_amount,
                    'is_fully_paid' => $booking->paid_amount >= $booking->total_amount,
                    'payment_count' => $payments->count(),
                ]
            ], 'Booking payments retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve booking payments', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve booking payments', 500);
        }
    }

    /**
     * Get payments by customer
     * 
     * @param int $customerId
     * @return JsonResponse
     */
    public function byCustomer(int $customerId): JsonResponse
    {
        try {
            $payments = $this->paymentRepository->getByCustomer($customerId);
            
            return $this->successResponse($payments, 'Customer payments retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve customer payments', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve customer payments', 500);
        }
    }

    /**
     * Export payment data in various formats
     * 
     * @param Request $request
     * @return mixed
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,excel,pdf',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:' . implode(',', \App\Models\Payment::VALID_STATUSES),
            'payment_method' => 'nullable|in:' . implode(',', \App\Models\Payment::VALID_METHODS)
        ]);
        
        try {
            // This would typically use Laravel Excel or similar package
            // For now, return a JSON response indicating the export would be processed
            
            $filters = $request->only(['start_date', 'end_date', 'status', 'payment_method']);
            
            return $this->successResponse([
                'message' => 'Export request received',
                'format' => $request->format,
                'filters' => $filters,
                'estimated_records' => $this->paymentRepository->getFilteredPaginated($filters)->total(),
                'download_url' => '/api/admin/crud/payments/download/' . uniqid()
            ], 'Export initiated successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to export payments', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to export payments', 500);
        }
    }

    /**
     * Remove the specified payment
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $payment = $this->paymentRepository->findOrFail($id);
            
            // Prevent deletion of completed payments
            if ($payment->status === 'completed') {
                return $this->errorResponse('Cannot delete completed payment', 400);
            }
            
            $this->paymentRepository->delete($id);
            
            return $this->successResponse(null, 'Payment deleted successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to delete payment', [
                'payment_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to delete payment', 500);
        }
    }
}
