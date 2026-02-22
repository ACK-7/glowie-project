<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\ActivityLog;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Payment Service
 * 
 * Handles all business logic related to payment processing including
 * payment creation, status management, refund processing, overdue detection,
 * and financial reporting.
 */
class PaymentService
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private NotificationService $notificationService
    ) {}

    /**
     * Create a new payment with comprehensive validation
     */
    public function createPayment(array $data): Payment
    {
        DB::beginTransaction();
        
        try {
            // Validate payment data
            $this->validatePaymentData($data);
            
            // Set default values
            $data['status'] = Payment::STATUS_PENDING;
            
            // Create the payment
            $payment = $this->paymentRepository->create($data);
            
            // Send payment created notification
            $this->notificationService->sendPaymentCreatedNotification($payment);
            
            // Create audit trail
            $this->createAuditTrail('payment_created', $payment, [
                'action' => 'Payment created',
                'user_id' => auth()->id(),
                'payment_data' => $data
            ]);
            
            DB::commit();
            
            Log::info('Payment created successfully', [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'booking_id' => $payment->booking_id,
                'amount' => $payment->amount,
                'method' => $payment->payment_method
            ]);
            
            return $payment;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create payment', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Process payment completion
     */
    public function completePayment(int $paymentId, string $transactionId = null, array $metadata = []): Payment
    {
        DB::beginTransaction();
        
        try {
            $payment = $this->paymentRepository->findOrFail($paymentId);
            
            // Validate payment can be completed
            if (!$payment->canTransitionTo(Payment::STATUS_COMPLETED)) {
                throw new Exception("Payment cannot be completed from {$payment->status} status");
            }
            
            // Complete the payment
            if (!$payment->complete($transactionId, $metadata)) {
                throw new Exception('Failed to complete payment');
            }
            
            // Update booking paid amount
            $booking = $payment->booking;
            $booking->increment('paid_amount', $payment->amount);
            
            // Check if booking is fully paid
            $this->checkBookingPaymentStatus($booking);
            
            // Send completion notification
            $this->notificationService->sendPaymentCompletedNotification($payment);
            
            // Create audit trail
            $this->createAuditTrail('payment_completed', $payment, [
                'transaction_id' => $transactionId,
                'metadata' => $metadata,
                'completed_by' => auth()->id(),
                'completed_at' => now()
            ]);
            
            DB::commit();
            
            Log::info('Payment completed successfully', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
                'amount' => $payment->amount
            ]);
            
            return $payment->fresh();
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete payment', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process payment refund with validation
     */
    public function processRefund(int $paymentId, float $refundAmount = null, string $reason = null): Payment
    {
        DB::beginTransaction();
        
        try {
            $payment = $this->paymentRepository->findOrFail($paymentId);
            
            // Validate refund eligibility
            if (!$payment->is_refundable) {
                throw new Exception('Payment is not eligible for refund');
            }
            
            $refundAmount = $refundAmount ?? $payment->amount;
            
            // Validate refund amount
            if ($refundAmount <= 0 || $refundAmount > $payment->amount) {
                throw new Exception('Invalid refund amount');
            }
            
            // Process the refund
            $refundPayment = $payment->refund($refundAmount, $reason);
            
            if (!$refundPayment) {
                throw new Exception('Failed to process refund');
            }
            
            // Send refund notification
            $this->notificationService->sendRefundProcessedNotification($payment, $refundPayment, $reason);
            
            // Create audit trail
            $this->createAuditTrail('payment_refunded', $payment, [
                'refund_amount' => $refundAmount,
                'refund_payment_id' => $refundPayment->id,
                'reason' => $reason,
                'refunded_by' => auth()->id(),
                'refunded_at' => now()
            ]);
            
            DB::commit();
            
            Log::info('Payment refund processed successfully', [
                'original_payment_id' => $payment->id,
                'refund_payment_id' => $refundPayment->id,
                'refund_amount' => $refundAmount,
                'reason' => $reason
            ]);
            
            return $refundPayment;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to process refund', [
                'payment_id' => $paymentId,
                'refund_amount' => $refundAmount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Detect and process overdue payments
     */
    public function processOverduePayments(int $days = 30): array
    {
        $results = [
            'processed' => 0,
            'notifications_sent' => 0,
            'escalations' => 0,
            'errors' => []
        ];
        
        try {
            $overduePayments = $this->paymentRepository->getOverdue($days);
            
            foreach ($overduePayments as $payment) {
                try {
                    DB::beginTransaction();
                    
                    $daysOverdue = $payment->days_overdue;
                    
                    // Send appropriate notification based on overdue period
                    if ($daysOverdue <= 7) {
                        // Gentle reminder
                        $this->notificationService->sendPaymentReminderNotification($payment, 'gentle');
                    } elseif ($daysOverdue <= 14) {
                        // Urgent reminder
                        $this->notificationService->sendPaymentReminderNotification($payment, 'urgent');
                    } else {
                        // Escalation
                        $this->notificationService->sendPaymentEscalationNotification($payment);
                        $results['escalations']++;
                    }
                    
                    $results['notifications_sent']++;
                    $results['processed']++;
                    
                    // Create audit trail
                    $this->createAuditTrail('overdue_payment_processed', $payment, [
                        'days_overdue' => $daysOverdue,
                        'action_taken' => $daysOverdue > 14 ? 'escalation' : 'reminder',
                        'processed_at' => now()
                    ]);
                    
                    DB::commit();
                    
                } catch (Exception $e) {
                    DB::rollBack();
                    $results['errors'][] = [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Failed to process overdue payment', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            Log::info('Overdue payments processed', $results);
            
        } catch (Exception $e) {
            Log::error('Failed to process overdue payments', [
                'error' => $e->getMessage()
            ]);
            $results['errors'][] = ['general' => $e->getMessage()];
        }
        
        return $results;
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStatistics(): array
    {
        return $this->paymentRepository->getPaymentStatistics();
    }

    /**
     * Get payments requiring attention
     */
    public function getPaymentsRequiringAttention(): Collection
    {
        return $this->paymentRepository->getRequiringAttention();
    }

    /**
     * Search payments
     */
    public function searchPayments(string $query): Collection
    {
        return $this->paymentRepository->searchPayments($query);
    }

    /**
     * Get payments with filters
     */
    public function getPaymentsWithFilters(array $filters): Collection
    {
        return $this->paymentRepository->getFilteredPaginated($filters);
    }

    /**
     * Calculate payment fees
     */
    public function calculatePaymentFees(float $amount, string $method): array
    {
        $payment = new Payment(['amount' => $amount, 'payment_method' => $method]);
        return $payment->calculateFees();
    }

    /**
     * Get payment instructions
     */
    public function getPaymentInstructions(int $paymentId): array
    {
        $payment = $this->paymentRepository->findOrFail($paymentId);
        return $payment->getPaymentInstructions();
    }

    /**
     * Validate payment data
     */
    private function validatePaymentData(array $data): void
    {
        // Validate booking exists
        $booking = Booking::find($data['booking_id']);
        if (!$booking) {
            throw new Exception('Invalid booking ID');
        }
        
        // Validate customer exists
        $customer = Customer::find($data['customer_id']);
        if (!$customer) {
            throw new Exception('Invalid customer ID');
        }
        
        // Validate amount
        if ($data['amount'] <= 0) {
            throw new Exception('Payment amount must be greater than zero');
        }
        
        // Validate payment method
        if (!in_array($data['payment_method'], Payment::VALID_METHODS)) {
            throw new Exception('Invalid payment method');
        }
        
        // Check if payment would exceed booking total
        $totalPaid = $booking->paid_amount + $data['amount'];
        if ($totalPaid > $booking->total_amount) {
            throw new Exception('Payment amount would exceed booking total');
        }
    }

    /**
     * Check booking payment status and send notifications
     */
    private function checkBookingPaymentStatus(Booking $booking): void
    {
        try {
            $booking = $booking->fresh(); // Reload to get updated paid_amount
            
            if ($booking->paid_amount >= $booking->total_amount) {
                // Booking is fully paid
                $this->notificationService->sendBookingFullyPaidNotification($booking);
                
                // Create audit trail
                $this->createAuditTrail('booking_fully_paid', null, [
                    'booking_id' => $booking->id,
                    'total_amount' => $booking->total_amount,
                    'paid_amount' => $booking->paid_amount,
                    'completed_at' => now()
                ]);
                
                Log::info('Booking fully paid', [
                    'booking_id' => $booking->id,
                    'total_amount' => $booking->total_amount,
                    'paid_amount' => $booking->paid_amount
                ]);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to check booking payment status', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create audit trail entry
     */
    private function createAuditTrail(string $action, ?Payment $payment, array $details): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $payment ? Payment::class : null,
            'model_id' => $payment?->id,
            'changes' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}