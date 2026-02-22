<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_reference',
        'customer_id',
        'vehicle_id',
        'vehicle_details',
        'route_id',
        'base_price',
        'additional_fees',
        'total_amount',
        'currency',
        'status',
        'valid_until',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'vehicle_details' => 'array',
        'additional_fees' => 'array',
        'base_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'valid_until' => 'date',
        'approved_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'currency' => 'USD',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CONVERTED = 'converted';
    const STATUS_EXPIRED = 'expired';

    const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CONVERTED,
        self::STATUS_EXPIRED,
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quote) {
            if (empty($quote->quote_reference)) {
                // Generate reference with retry logic to handle unique constraint violations
                $maxRetries = 5;
                $retry = 0;
                
                while ($retry < $maxRetries) {
                    try {
                        $quote->quote_reference = $quote->generateQuoteReference();
                        break; // Success, exit loop
                    } catch (\Exception $e) {
                        $retry++;
                        if ($retry >= $maxRetries) {
                            // Last resort: use timestamp-based reference
                            $prefix = 'QT';
                            $year = date('Y');
                            $month = date('m');
                            $timestamp = (int)(microtime(true) * 10000) % 100000;
                            $quote->quote_reference = $prefix . $year . $month . str_pad($timestamp, 5, '0', STR_PAD_LEFT);
                            break;
                        }
                        // Small delay before retry
                        usleep(10000); // 10ms
                    }
                }
            }
            
            if (empty($quote->valid_until)) {
                $quote->valid_until = now()->addDays(30);
            }
        });

        static::created(function ($quote) {
            ActivityLog::logActivity('created', self::class, $quote->id, $quote->toArray());
        });

        static::updated(function ($quote) {
            if ($quote->wasChanged()) {
                ActivityLog::logActivity('updated', self::class, $quote->id, $quote->getChanges());
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

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now())
                    ->where('status', '!=', self::STATUS_CONVERTED);
    }

    public function scopeValid($query)
    {
        return $query->where('valid_until', '>=', now())
                    ->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByRoute($query, $routeId)
    {
        return $query->where('route_id', $routeId);
    }

    /**
     * Accessors
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until && 
               $this->valid_until->isPast() && 
               $this->status !== self::STATUS_CONVERTED;
    }

    public function getIsValidAttribute(): bool
    {
        return $this->valid_until && 
               $this->valid_until->isFuture() && 
               in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        if (!$this->valid_until) {
            return 0;
        }
        
        return max(0, now()->diffInDays($this->valid_until, false));
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getVehicleDescriptionAttribute(): string
    {
        if (!$this->vehicle_details) {
            return 'Vehicle details not available';
        }

        $details = $this->vehicle_details;
        return sprintf(
            '%s %s %s (%s)',
            $details['year'] ?? '',
            $details['make'] ?? '',
            $details['model'] ?? '',
            $details['color'] ?? ''
        );
    }

    public function getTotalFeesAttribute(): float
    {
        if (!$this->additional_fees) {
            return 0;
        }

        return collect($this->additional_fees)->sum('amount');
    }

    /**
     * Business Logic Methods
     */
    public function generateQuoteReference(): string
    {
        $prefix = 'QT';
        $year = date('Y');
        $month = date('m');
        
        // Use database transaction with lock to prevent race conditions
        return DB::transaction(function () use ($prefix, $year, $month) {
            // Get the next sequence number for this month with row lock
            $lastQuote = self::where('quote_reference', 'like', "{$prefix}{$year}{$month}%")
                            ->lockForUpdate()
                            ->orderBy('quote_reference', 'desc')
                            ->first();
            
            $sequence = 1;
            if ($lastQuote && $lastQuote->quote_reference) {
                $lastSequence = (int) substr($lastQuote->quote_reference, -4);
                $sequence = $lastSequence + 1;
            }
            
            // Generate reference and verify it doesn't exist
            $maxAttempts = 20;
            $attempt = 0;
            
            while ($attempt < $maxAttempts) {
                $reference = $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
                
                // Check if this reference already exists
                $exists = self::where('quote_reference', $reference)->exists();
                
                if (!$exists) {
                    return $reference;
                }
                
                // If it exists, increment and try again
                $sequence++;
                $attempt++;
            }
            
            // Fallback: use timestamp-based reference if all attempts fail
            $timestamp = (int)(microtime(true) * 10000) % 100000;
            return $prefix . $year . $month . str_pad($timestamp, 5, '0', STR_PAD_LEFT);
        });
    }

    public function approve(int $userId = null, string $notes = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $previousStatus = $this->status;
        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $userId ?? auth()->id();
        $this->approved_at = now();
        
        if ($notes) {
            $this->notes = $notes;
        }

        if ($this->save()) {
            ActivityLog::logActivity(
                'approved',
                self::class,
                $this->id,
                ['approved_by' => $this->approved_by, 'notes' => $notes]
            );
            
            // Broadcast real-time update
            $realTimeService = app(\App\Services\RealTimeService::class);
            $approvedBy = $this->approvedBy;
            $realTimeService->broadcastQuoteStatusUpdate($this, $previousStatus, $approvedBy);
            
            return true;
        }
        
        return false;
    }

    public function reject(string $reason, int $userId = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $previousStatus = $this->status;
        $this->status = self::STATUS_REJECTED;
        $this->notes = $reason;

        if ($this->save()) {
            ActivityLog::logActivity(
                'rejected',
                self::class,
                $this->id,
                ['rejected_by' => $userId ?? auth()->id(), 'reason' => $reason]
            );
            
            // Broadcast real-time update
            $realTimeService = app(\App\Services\RealTimeService::class);
            $rejectedBy = User::find($userId ?? auth()->id());
            $realTimeService->broadcastQuoteStatusUpdate($this, $previousStatus, $rejectedBy);
            
            return true;
        }
        
        return false;
    }

    public function convertToBooking(array $additionalData = []): ?Booking
    {
        if ($this->status !== self::STATUS_APPROVED || $this->is_expired) {
            return null;
        }

        $bookingData = array_merge([
            'customer_id' => $this->customer_id,
            'quote_id' => $this->id,
            'route_id' => $this->route_id,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'created_by' => auth()->id(),
        ], $additionalData);

        // Create vehicle record if vehicle_details provided
        if ($this->vehicle_details) {
            $vehicle = Vehicle::create($this->vehicle_details);
            $bookingData['vehicle_id'] = $vehicle->id;
        }

        $booking = Booking::create($bookingData);

        if ($booking) {
            $previousStatus = $this->status;
            $this->status = self::STATUS_CONVERTED;
            $this->save();

            ActivityLog::logActivity(
                'converted_to_booking',
                self::class,
                $this->id,
                ['booking_id' => $booking->id]
            );

            // Broadcast real-time update for quote conversion
            $realTimeService = app(\App\Services\RealTimeService::class);
            $convertedBy = auth()->user();
            $realTimeService->broadcastQuoteStatusUpdate($this, $previousStatus, $convertedBy);

            return $booking;
        }

        return null;
    }

    public function extendValidity(int $days): bool
    {
        if ($this->status === self::STATUS_CONVERTED) {
            return false;
        }

        $this->valid_until = $this->valid_until->addDays($days);
        
        if ($this->status === self::STATUS_EXPIRED) {
            $this->status = self::STATUS_PENDING;
        }

        return $this->save();
    }

    public function calculateTotalAmount(): float
    {
        $total = $this->base_price;
        
        if ($this->additional_fees) {
            foreach ($this->additional_fees as $fee) {
                $total += $fee['amount'] ?? 0;
            }
        }
        
        return $total;
    }

    public function addFee(string $name, float $amount, string $description = null): void
    {
        $fees = $this->additional_fees ?? [];
        
        $fees[] = [
            'name' => $name,
            'amount' => $amount,
            'description' => $description,
        ];
        
        $this->additional_fees = $fees;
        $this->total_amount = $this->calculateTotalAmount();
    }

    public function removeFee(string $name): void
    {
        if (!$this->additional_fees) {
            return;
        }

        $fees = collect($this->additional_fees)->reject(function ($fee) use ($name) {
            return $fee['name'] === $name;
        })->values()->toArray();

        $this->additional_fees = $fees;
        $this->total_amount = $this->calculateTotalAmount();
    }

    /**
     * Check if quote should be automatically expired
     */
    public function checkAndUpdateExpiry(): bool
    {
        if ($this->is_expired && $this->status !== self::STATUS_EXPIRED) {
            $this->status = self::STATUS_EXPIRED;
            return $this->save();
        }
        
        return false;
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'route_id' => 'required|exists:routes,id',
            'vehicle_details' => 'required|array',
            'vehicle_details.make' => 'required|string|max:100',
            'vehicle_details.model' => 'required|string|max:100',
            'vehicle_details.year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'base_price' => 'required|numeric|min:0',
            'additional_fees' => 'nullable|array',
            'additional_fees.*.name' => 'required|string|max:100',
            'additional_fees.*.amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'valid_until' => 'required|date|after:today',
            'status' => 'required|in:' . implode(',', self::VALID_STATUSES),
        ];
    }
}
