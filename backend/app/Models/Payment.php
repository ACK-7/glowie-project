<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_reference',
        'booking_id',
        'customer_id',
        'amount',
        'currency',
        'payment_method',
        'payment_gateway',
        'transaction_id',
        'status',
        'payment_date',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'pending',
        'currency' => 'USD',
    ];

    // Payment method constants
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_MOBILE_MONEY = 'mobile_money';
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_CASH = 'cash';

    const VALID_METHODS = [
        self::METHOD_BANK_TRANSFER,
        self::METHOD_MOBILE_MONEY,
        self::METHOD_CREDIT_CARD,
        self::METHOD_CASH,
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CANCELLED = 'cancelled';

    const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_REFUNDED,
        self::STATUS_CANCELLED,
    ];

    // Status transition rules
    const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED],
        self::STATUS_COMPLETED => [self::STATUS_REFUNDED],
        self::STATUS_FAILED => [self::STATUS_PENDING, self::STATUS_CANCELLED],
        self::STATUS_REFUNDED => [],
        self::STATUS_CANCELLED => [],
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_reference)) {
                $payment->payment_reference = $payment->generatePaymentReference();
            }
        });

        static::created(function ($payment) {
            ActivityLog::logActivity('created', self::class, $payment->id, $payment->toArray());
        });

        static::updated(function ($payment) {
            if ($payment->wasChanged()) {
                ActivityLog::logActivity('updated', self::class, $payment->id, $payment->getChanges());
                
                // Update booking paid amount when payment is completed
                if ($payment->wasChanged('status') && $payment->status === self::STATUS_COMPLETED) {
                    $payment->booking->increment('paid_amount', $payment->amount);
                }
            }
        });
    }

    /**
     * Relationships
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scopes
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByBooking($query, $bookingId)
    {
        return $query->where('booking_id', $bookingId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    public function scopeOverdue($query, $days = 30)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where('created_at', '<', now()->subDays($days));
    }

    public function scopeByAmountRange($query, $minAmount, $maxAmount)
    {
        return $query->whereBetween('amount', [$minAmount, $maxAmount]);
    }

    /**
     * Accessors
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function getMethodLabelAttribute(): string
    {
        $labels = [
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_MOBILE_MONEY => 'Mobile Money',
            self::METHOD_CREDIT_CARD => 'Credit Card',
            self::METHOD_CASH => 'Cash',
        ];

        return $labels[$this->payment_method] ?? 'Unknown';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING && 
               $this->created_at->addDays(30)->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) {
            return 0;
        }
        
        return $this->created_at->addDays(30)->diffInDays(now());
    }

    public function getIsRefundableAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED && 
               $this->payment_date && 
               $this->payment_date->addDays(90)->isFuture(); // 90-day refund window
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getProcessingTimeAttribute(): ?int
    {
        if ($this->status !== self::STATUS_COMPLETED || !$this->payment_date) {
            return null;
        }
        
        return $this->created_at->diffInMinutes($this->payment_date);
    }

    /**
     * Business Logic Methods
     */
    public function generatePaymentReference(): string
    {
        $prefix = 'PAY';
        $year = date('Y');
        $month = date('m');
        
        // Get the next sequence number for this month
        $lastPayment = self::where('payment_reference', 'like', "{$prefix}{$year}{$month}%")
                          ->orderBy('payment_reference', 'desc')
                          ->first();
        
        if ($lastPayment) {
            $lastSequence = (int) substr($lastPayment->payment_reference, -6);
            $sequence = $lastSequence + 1;
        } else {
            $sequence = 1;
        }
        
        return $prefix . $year . $month . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        if (!in_array($newStatus, self::VALID_STATUSES)) {
            return false;
        }

        $allowedTransitions = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($newStatus, $allowedTransitions);
    }

    public function complete(string $transactionId = null, array $metadata = []): bool
    {
        if (!$this->canTransitionTo(self::STATUS_COMPLETED)) {
            return false;
        }

        $this->status = self::STATUS_COMPLETED;
        $this->payment_date = now();
        
        if ($transactionId) {
            $this->transaction_id = $transactionId;
        }
        
        if (!empty($metadata)) {
            $this->metadata = array_merge($this->metadata ?? [], $metadata);
        }

        if ($this->save()) {
            ActivityLog::logActivity(
                'completed',
                self::class,
                $this->id,
                [
                    'transaction_id' => $this->transaction_id,
                    'amount' => $this->amount,
                    'method' => $this->payment_method
                ]
            );
            
            return true;
        }
        
        return false;
    }

    public function fail(string $reason = null, array $metadata = []): bool
    {
        if (!$this->canTransitionTo(self::STATUS_FAILED)) {
            return false;
        }

        $this->status = self::STATUS_FAILED;
        
        if ($reason) {
            $this->notes = $reason;
        }
        
        if (!empty($metadata)) {
            $this->metadata = array_merge($this->metadata ?? [], $metadata);
        }

        if ($this->save()) {
            ActivityLog::logActivity(
                'failed',
                self::class,
                $this->id,
                ['reason' => $reason, 'metadata' => $metadata]
            );
            
            return true;
        }
        
        return false;
    }

    public function refund(float $refundAmount = null, string $reason = null): ?Payment
    {
        if (!$this->canTransitionTo(self::STATUS_REFUNDED)) {
            return null;
        }

        $refundAmount = $refundAmount ?? $this->amount;
        
        if ($refundAmount > $this->amount) {
            return null; // Cannot refund more than original amount
        }

        // Create refund payment record
        $refund = self::create([
            'booking_id' => $this->booking_id,
            'customer_id' => $this->customer_id,
            'amount' => -$refundAmount, // Negative amount for refund
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'payment_gateway' => $this->payment_gateway,
            'status' => self::STATUS_COMPLETED,
            'payment_date' => now(),
            'notes' => "Refund for payment {$this->payment_reference}. Reason: {$reason}",
            'metadata' => [
                'original_payment_id' => $this->id,
                'refund_reason' => $reason,
                'refund_type' => $refundAmount == $this->amount ? 'full' : 'partial',
            ],
        ]);

        // Update original payment status if full refund
        if ($refundAmount == $this->amount) {
            $this->status = self::STATUS_REFUNDED;
            $this->save();
        }

        // Update booking paid amount
        $this->booking->decrement('paid_amount', $refundAmount);

        ActivityLog::logActivity(
            'refunded',
            self::class,
            $this->id,
            [
                'refund_amount' => $refundAmount,
                'refund_payment_id' => $refund->id,
                'reason' => $reason
            ]
        );

        return $refund;
    }

    public function cancel(string $reason = null): bool
    {
        if (!$this->canTransitionTo(self::STATUS_CANCELLED)) {
            return false;
        }

        $this->status = self::STATUS_CANCELLED;
        
        if ($reason) {
            $this->notes = $reason;
        }

        if ($this->save()) {
            ActivityLog::logActivity(
                'cancelled',
                self::class,
                $this->id,
                ['reason' => $reason]
            );
            
            return true;
        }
        
        return false;
    }

    public function retry(): bool
    {
        if ($this->status !== self::STATUS_FAILED) {
            return false;
        }

        $this->status = self::STATUS_PENDING;
        $this->notes = null;
        $this->metadata = array_merge($this->metadata ?? [], ['retry_at' => now()]);

        return $this->save();
    }

    public function addMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
    }

    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    public function calculateFees(): array
    {
        $fees = [];
        
        switch ($this->payment_method) {
            case self::METHOD_CREDIT_CARD:
                $fees['processing_fee'] = $this->amount * 0.029; // 2.9%
                $fees['gateway_fee'] = 0.30; // $0.30 fixed fee
                break;
                
            case self::METHOD_MOBILE_MONEY:
                $fees['processing_fee'] = $this->amount * 0.015; // 1.5%
                break;
                
            case self::METHOD_BANK_TRANSFER:
                $fees['processing_fee'] = min($this->amount * 0.005, 25.00); // 0.5% max $25
                break;
                
            case self::METHOD_CASH:
                $fees['processing_fee'] = 0;
                break;
        }
        
        $fees['total_fees'] = array_sum($fees);
        $fees['net_amount'] = $this->amount - $fees['total_fees'];
        
        return $fees;
    }

    public function getPaymentInstructions(): array
    {
        $instructions = [];
        
        switch ($this->payment_method) {
            case self::METHOD_BANK_TRANSFER:
                $instructions = [
                    'method' => 'Bank Transfer',
                    'steps' => [
                        'Transfer the amount to our bank account',
                        'Use payment reference as transfer description',
                        'Send proof of payment to our support team',
                    ],
                    'details' => [
                        'account_name' => 'ShipWithGlowie Auto Ltd',
                        'account_number' => '1234567890',
                        'bank_name' => 'Example Bank',
                        'reference' => $this->payment_reference,
                    ],
                ];
                break;
                
            case self::METHOD_MOBILE_MONEY:
                $instructions = [
                    'method' => 'Mobile Money',
                    'steps' => [
                        'Dial *165# on your mobile phone',
                        'Select Send Money option',
                        'Enter our business number',
                        'Enter the exact amount',
                        'Use payment reference in description',
                    ],
                    'details' => [
                        'business_number' => '123456',
                        'amount' => $this->formatted_amount,
                        'reference' => $this->payment_reference,
                    ],
                ];
                break;
        }
        
        return $instructions;
    }

    public static function getTotalRevenue(Carbon $startDate = null, Carbon $endDate = null): float
    {
        $query = self::where('status', self::STATUS_COMPLETED);
        
        if ($startDate) {
            $query->where('payment_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('payment_date', '<=', $endDate);
        }
        
        return $query->sum('amount');
    }

    public static function getRevenueByMethod(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = self::where('status', self::STATUS_COMPLETED);
        
        if ($startDate) {
            $query->where('payment_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('payment_date', '<=', $endDate);
        }
        
        return $query->selectRaw('payment_method, SUM(amount) as total')
                    ->groupBy('payment_method')
                    ->pluck('total', 'payment_method')
                    ->toArray();
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'booking_id' => 'required|exists:bookings,id',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'payment_method' => 'required|in:' . implode(',', self::VALID_METHODS),
            'payment_gateway' => 'nullable|string|max:50',
            'transaction_id' => 'nullable|string|max:100',
            'status' => 'required|in:' . implode(',', self::VALID_STATUSES),
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateValidationRules(): array
    {
        $rules = self::validationRules();
        
        // Make some fields optional for updates
        $rules['booking_id'] = 'sometimes|' . $rules['booking_id'];
        $rules['customer_id'] = 'sometimes|' . $rules['customer_id'];
        $rules['amount'] = 'sometimes|' . $rules['amount'];
        $rules['payment_method'] = 'sometimes|' . $rules['payment_method'];
        $rules['status'] = 'sometimes|' . $rules['status'];
        
        return $rules;
    }
}