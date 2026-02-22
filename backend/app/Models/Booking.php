<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_reference',
        'customer_id',
        'quote_id',
        'vehicle_id',
        'route_id',
        'status',
        'pickup_date',
        'delivery_date',
        'estimated_delivery',
        'total_amount',
        'paid_amount',
        'currency',
        'notes',
        'created_by',
        'updated_by',
        'special_instructions',
        'recipient_name',
        'recipient_phone',
        'recipient_email',
        'recipient_country',
        'recipient_city',
        'recipient_address'
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'delivery_date' => 'date',
        'estimated_delivery' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'pending',
        'currency' => 'USD',
        'paid_amount' => 0.00,
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_IN_TRANSIT,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    // Status transition rules
    const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED => [self::STATUS_IN_TRANSIT, self::STATUS_CANCELLED],
        self::STATUS_IN_TRANSIT => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
        self::STATUS_DELIVERED => [],
        self::STATUS_CANCELLED => [],
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_reference)) {
                $booking->booking_reference = $booking->generateBookingReference();
            }
        });

        static::created(function ($booking) {
            ActivityLog::logActivity('created', self::class, $booking->id, $booking->toArray());
        });

        static::updated(function ($booking) {
            if ($booking->wasChanged()) {
                ActivityLog::logActivity('updated', self::class, $booking->id, $booking->getChanges());
            }
        });
    }

    /**
     * Relationships
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_IN_TRANSIT]);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByRoute($query, $routeId)
    {
        return $query->where('route_id', $routeId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('estimated_delivery', '<', now())
                    ->whereNotIn('status', [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    /**
     * Accessors
     */
    public function getBalanceAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function getPaymentStatusAttribute()
    {
        if ($this->paid_amount >= $this->total_amount) {
            return 'paid';
        } elseif ($this->paid_amount > 0) {
            return 'partial';
        }
        return 'unpaid';
    }

    public function getIsOverdueAttribute()
    {
        return $this->estimated_delivery && 
               $this->estimated_delivery->isPast() && 
               !in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Business Logic Methods
     */
    public function generateBookingReference(): string
    {
        $prefix = 'BK';
        $year = date('Y');
        $month = date('m');
        
        // Get the next sequence number for this month
        $lastBooking = self::where('booking_reference', 'like', "{$prefix}{$year}{$month}%")
                          ->orderBy('booking_reference', 'desc')
                          ->first();
        
        if ($lastBooking) {
            $lastSequence = (int) substr($lastBooking->booking_reference, -4);
            $sequence = $lastSequence + 1;
        } else {
            $sequence = 1;
        }
        
        return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        if (!in_array($newStatus, self::VALID_STATUSES)) {
            return false;
        }

        $allowedTransitions = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($newStatus, $allowedTransitions);
    }

    public function updateStatus(string $newStatus, string $reason = null): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;
        $this->updated_by = auth()->id();
        
        if ($this->save()) {
            ActivityLog::logActivity(
                'status_changed',
                self::class,
                $this->id,
                [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason
                ]
            );
            
            return true;
        }
        
        return false;
    }

    public function addPayment(float $amount, string $method = 'bank_transfer'): Payment
    {
        $payment = $this->payments()->create([
            'customer_id' => $this->customer_id,
            'amount' => $amount,
            'currency' => $this->currency,
            'payment_method' => $method,
            'status' => 'completed',
            'payment_date' => now(),
        ]);

        $this->increment('paid_amount', $amount);
        
        return $payment;
    }

    public function calculateProgress(): array
    {
        $statusOrder = [
            self::STATUS_PENDING => 0,
            self::STATUS_CONFIRMED => 25,
            self::STATUS_IN_TRANSIT => 50,
            self::STATUS_DELIVERED => 100,
            self::STATUS_CANCELLED => 0,
        ];

        $progress = $statusOrder[$this->status] ?? 0;
        
        return [
            'percentage' => $progress,
            'status' => $this->status,
            'label' => $this->status_label,
        ];
    }

    public function getRequiredDocuments(): array
    {
        // This would typically be based on route requirements
        return [
            'passport' => 'Customer Passport',
            'license' => 'Driving License',
            'invoice' => 'Purchase Invoice',
            'insurance' => 'Insurance Certificate',
        ];
    }

    public function getMissingDocuments(): array
    {
        $required = $this->getRequiredDocuments();
        $uploaded = $this->documents()->pluck('document_type')->toArray();
        
        return array_diff_key($required, array_flip($uploaded));
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'route_id' => 'required|exists:routes,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'status' => 'required|in:' . implode(',', self::VALID_STATUSES),
            'pickup_date' => 'nullable|date|after_or_equal:today',
            'delivery_date' => 'nullable|date|after:pickup_date',
            'estimated_delivery' => 'nullable|date|after:pickup_date',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0|lte:total_amount',
            'currency' => 'required|string|size:3',
            'recipient_name' => 'nullable|string|max:255',
            'recipient_phone' => 'nullable|string|max:20',
            'recipient_email' => 'nullable|email|max:255',
            'recipient_country' => 'required|string|max:100',
            'recipient_city' => 'required|string|max:100',
            'recipient_address' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public static function updateValidationRules(int $id = null): array
    {
        return [
            'customer_id' => 'sometimes|exists:customers,id',
            'vehicle_id' => 'sometimes|exists:vehicles,id',
            'route_id' => 'sometimes|exists:routes,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'status' => 'sometimes|in:' . implode(',', self::VALID_STATUSES),
            'service_type' => 'nullable|string|max:50',
            'pickup_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'estimated_delivery' => 'nullable|date',
            'total_amount' => 'sometimes|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'recipient_name' => 'nullable|string|max:255',
            'recipient_phone' => 'nullable|string|max:20',
            'recipient_email' => 'nullable|email|max:255',
            'recipient_country' => 'nullable|string|max:100',
            'recipient_city' => 'nullable|string|max:100',
            'recipient_address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'special_instructions' => 'nullable|string|max:1000',
        ];
    }
}
