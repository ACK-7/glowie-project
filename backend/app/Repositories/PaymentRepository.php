<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Payment Repository Implementation
 */
class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    /**
     * Get payments by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->byStatus($status)->get();
    }

    /**
     * Get pending payments
     */
    public function getPending(): Collection
    {
        return $this->model->pending()->get();
    }

    /**
     * Get completed payments
     */
    public function getCompleted(): Collection
    {
        return $this->model->completed()->get();
    }

    /**
     * Get failed payments
     */
    public function getFailed(): Collection
    {
        return $this->model->failed()->get();
    }

    /**
     * Get refunded payments
     */
    public function getRefunded(): Collection
    {
        return $this->model->refunded()->get();
    }

    /**
     * Get payments by method
     */
    public function getByMethod(string $method): Collection
    {
        return $this->model->byMethod($method)->get();
    }

    /**
     * Get payments by customer
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->model->byCustomer($customerId)->get();
    }

    /**
     * Get payments by booking
     */
    public function getByBooking(int $bookingId): Collection
    {
        return $this->model->byBooking($bookingId)->get();
    }

    /**
     * Get overdue payments
     */
    public function getOverdue(int $days = 30): Collection
    {
        return $this->model->overdue($days)->get();
    }

    /**
     * Get payments by amount range
     */
    public function getByAmountRange(float $minAmount, float $maxAmount): Collection
    {
        return $this->model->byAmountRange($minAmount, $maxAmount)->get();
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenue(Carbon $startDate = null, Carbon $endDate = null): float
    {
        return Payment::getTotalRevenue($startDate, $endDate);
    }

    /**
     * Get revenue by method
     */
    public function getRevenueByMethod(Carbon $startDate = null, Carbon $endDate = null): array
    {
        return Payment::getRevenueByMethod($startDate, $endDate);
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStatistics(): array
    {
        $totalPayments = $this->model->count();
        $completedPayments = $this->model->where('status', Payment::STATUS_COMPLETED)->count();
        $pendingPayments = $this->model->where('status', Payment::STATUS_PENDING)->count();
        $failedPayments = $this->model->where('status', Payment::STATUS_FAILED)->count();
        
        return [
            'total_payments' => $totalPayments,
            'completed_payments' => $completedPayments,
            'pending_payments' => $pendingPayments,
            'failed_payments' => $failedPayments,
            'refunded_payments' => $this->model->where('status', Payment::STATUS_REFUNDED)->count(),
            'cancelled_payments' => $this->model->where('status', Payment::STATUS_CANCELLED)->count(),
            'success_rate' => $totalPayments > 0 ? ($completedPayments / $totalPayments) * 100 : 0,
            'failure_rate' => $totalPayments > 0 ? ($failedPayments / $totalPayments) * 100 : 0,
            'total_revenue' => $this->model->where('status', Payment::STATUS_COMPLETED)->sum('amount'),
            'average_payment_amount' => $this->model->where('status', Payment::STATUS_COMPLETED)->avg('amount'),
            'overdue_payments' => $this->model->overdue()->count(),
            'overdue_amount' => $this->model->overdue()->sum('amount'),
        ];
    }

    /**
     * Get revenue trends
     */
    public function getRevenueTrends(int $days = 30): Collection
    {
        return $this->model
            ->select([
                DB::raw('DATE(payment_date) as date'),
                DB::raw('COUNT(*) as payment_count'),
                DB::raw('SUM(amount) as total_revenue'),
                DB::raw('AVG(amount) as average_amount'),
                'payment_method'
            ])
            ->where('status', Payment::STATUS_COMPLETED)
            ->where('payment_date', '>=', now()->subDays($days))
            ->groupBy(['date', 'payment_method'])
            ->orderBy('date')
            ->get();
    }

    /**
     * Get payment method performance
     */
    public function getPaymentMethodPerformance(): Collection
    {
        return $this->model
            ->select([
                'payment_method',
                DB::raw('COUNT(*) as total_attempts'),
                DB::raw('COUNT(CASE WHEN status = "completed" THEN 1 END) as successful_payments'),
                DB::raw('COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_payments'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_revenue'),
                DB::raw('AVG(CASE WHEN status = "completed" THEN amount END) as average_amount'),
                DB::raw('AVG(CASE WHEN status = "completed" AND payment_date IS NOT NULL 
                         THEN TIMESTAMPDIFF(MINUTE, created_at, payment_date) END) as avg_processing_time')
            ])
            ->groupBy('payment_method')
            ->orderBy('total_revenue', 'desc')
            ->get()
            ->map(function ($method) {
                $method->success_rate = $method->total_attempts > 0 ? 
                    ($method->successful_payments / $method->total_attempts) * 100 : 0;
                $method->failure_rate = $method->total_attempts > 0 ? 
                    ($method->failed_payments / $method->total_attempts) * 100 : 0;
                return $method;
            });
    }

    /**
     * Search payments
     */
    public function searchPayments(string $query): Collection
    {
        return $this->model
            ->with(['booking.customer', 'customer'])
            ->where(function ($q) use ($query) {
                $q->where('payment_reference', 'LIKE', "%{$query}%")
                  ->orWhere('transaction_id', 'LIKE', "%{$query}%")
                  ->orWhereHas('booking', function ($bookingQuery) use ($query) {
                      $bookingQuery->where('booking_reference', 'LIKE', "%{$query}%");
                  })
                  ->orWhereHas('customer', function ($customerQuery) use ($query) {
                      $customerQuery->where('first_name', 'LIKE', "%{$query}%")
                               ->orWhere('last_name', 'LIKE', "%{$query}%")
                               ->orWhere('email', 'LIKE', "%{$query}%");
                  });
            })
            ->get();
    }

    /**
     * Get recent payments
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model
            ->with(['booking.customer', 'customer'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get payments requiring attention
     */
    public function getRequiringAttention(): Collection
    {
        return $this->model
            ->with(['booking.customer', 'customer'])
            ->where(function ($query) {
                $query->where('status', Payment::STATUS_PENDING)
                      ->where('created_at', '<', now()->subDays(3))
                      ->orWhere('status', Payment::STATUS_FAILED)
                      ->orWhere(function ($q) {
                          $q->where('status', Payment::STATUS_PENDING)
                            ->where('created_at', '<', now()->subDays(7));
                      });
            })
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Find payment by reference
     */
    public function findByReference(string $reference): ?Payment
    {
        return $this->model->where('payment_reference', $reference)->first();
    }

    /**
     * Apply search filter for payments
     */
    protected function applySearchFilter(Builder $query, string $searchTerm): void
    {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('payment_reference', 'LIKE', "%{$searchTerm}%")
              ->orWhere('transaction_id', 'LIKE', "%{$searchTerm}%")
              ->orWhereHas('booking', function ($bookingQuery) use ($searchTerm) {
                  $bookingQuery->where('booking_reference', 'LIKE', "%{$searchTerm}%");
              })
              ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                  $customerQuery->where('first_name', 'LIKE', "%{$searchTerm}%")
                               ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
              });
        });
    }

    /**
     * Apply custom filters for payments
     */
    protected function applyCustomFilter(Builder $query, string $key, $value): void
    {
        switch ($key) {
            case 'payment_method':
                $query->where('payment_method', $value);
                break;
                
            case 'customer_id':
                $query->where('customer_id', $value);
                break;
                
            case 'booking_id':
                $query->where('booking_id', $value);
                break;
                
            case 'amount_min':
                $query->where('amount', '>=', $value);
                break;
                
            case 'amount_max':
                $query->where('amount', '<=', $value);
                break;
                
            case 'overdue':
                if ($value) {
                    $query->where('status', Payment::STATUS_PENDING)
                          ->where('created_at', '<', now()->subDays(30));
                }
                break;
                
            case 'currency':
                $query->where('currency', $value);
                break;
                
            case 'payment_gateway':
                $query->where('payment_gateway', 'LIKE', "%{$value}%");
                break;
        }
    }
}