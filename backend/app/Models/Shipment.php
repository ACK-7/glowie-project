<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_number',
        'booking_id',
        'carrier_name',
        'vessel_name',
        'container_number',
        'current_location',
        'status',
        'departure_port',
        'arrival_port',
        'departure_date',
        'estimated_arrival',
        'actual_arrival',
        'tracking_updates',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'estimated_arrival' => 'date',
        'actual_arrival' => 'date',
        'tracking_updates' => 'array',
    ];

    protected $attributes = [
        'status' => 'preparing',
    ];

    // Status constants
    const STATUS_PREPARING = 'preparing';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_CUSTOMS = 'customs';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_DELAYED = 'delayed';

    const VALID_STATUSES = [
        self::STATUS_PREPARING,
        self::STATUS_IN_TRANSIT,
        self::STATUS_CUSTOMS,
        self::STATUS_DELIVERED,
        self::STATUS_DELAYED,
    ];

    // Status transition rules
    const STATUS_TRANSITIONS = [
        self::STATUS_PREPARING => [self::STATUS_IN_TRANSIT, self::STATUS_DELAYED],
        self::STATUS_IN_TRANSIT => [self::STATUS_CUSTOMS, self::STATUS_DELIVERED, self::STATUS_DELAYED],
        self::STATUS_CUSTOMS => [self::STATUS_DELIVERED, self::STATUS_DELAYED],
        self::STATUS_DELIVERED => [],
        self::STATUS_DELAYED => [self::STATUS_IN_TRANSIT, self::STATUS_CUSTOMS, self::STATUS_DELIVERED],
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shipment) {
            if (empty($shipment->tracking_number)) {
                $shipment->tracking_number = $shipment->generateTrackingNumber();
            }
        });

        static::created(function ($shipment) {
            ActivityLog::logActivity('created', self::class, $shipment->id, $shipment->toArray());
        });

        static::updated(function ($shipment) {
            if ($shipment->wasChanged()) {
                ActivityLog::logActivity('updated', self::class, $shipment->id, $shipment->getChanges());
                
                // Update booking status when shipment is delivered
                if ($shipment->wasChanged('status') && $shipment->status === self::STATUS_DELIVERED) {
                    $shipment->booking->updateStatus(Booking::STATUS_DELIVERED, 'Shipment delivered');
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

    /**
     * Scopes
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DELIVERED]);
    }

    public function scopeInTransit($query)
    {
        return $query->whereIn('status', [self::STATUS_IN_TRANSIT, self::STATUS_CUSTOMS]);
    }

    public function scopeDelayed($query)
    {
        return $query->where('status', self::STATUS_DELAYED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('estimated_arrival', '<', now())
                    ->whereNotIn('status', [self::STATUS_DELIVERED]);
    }

    public function scopeByCarrier($query, $carrier)
    {
        return $query->where('carrier_name', 'like', "%{$carrier}%");
    }

    public function scopeByPort($query, $port, $type = 'both')
    {
        if ($type === 'departure') {
            return $query->where('departure_port', 'like', "%{$port}%");
        } elseif ($type === 'arrival') {
            return $query->where('arrival_port', 'like', "%{$port}%");
        }
        
        return $query->where(function ($q) use ($port) {
            $q->where('departure_port', 'like', "%{$port}%")
              ->orWhere('arrival_port', 'like', "%{$port}%");
        });
    }

    /**
     * Accessors
     */
    public function getIsDelayedAttribute(): bool
    {
        return $this->estimated_arrival && 
               $this->estimated_arrival->isPast() && 
               $this->status !== self::STATUS_DELIVERED;
    }

    public function getDaysDelayedAttribute(): int
    {
        if (!$this->is_delayed) {
            return 0;
        }
        
        return $this->estimated_arrival->diffInDays(now());
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getProgressPercentageAttribute(): int
    {
        $statusProgress = [
            self::STATUS_PREPARING => 10,
            self::STATUS_IN_TRANSIT => 50,
            self::STATUS_CUSTOMS => 80,
            self::STATUS_DELIVERED => 100,
            self::STATUS_DELAYED => 25, // Depends on where the delay occurred
        ];

        return $statusProgress[$this->status] ?? 0;
    }

    public function getEstimatedDaysRemainingAttribute(): int
    {
        if (!$this->estimated_arrival || $this->status === self::STATUS_DELIVERED) {
            return 0;
        }
        
        return max(0, now()->diffInDays($this->estimated_arrival, false));
    }

    public function getRouteDescriptionAttribute(): string
    {
        if ($this->departure_port && $this->arrival_port) {
            return "{$this->departure_port} → {$this->arrival_port}";
        }
        
        return 'Route not specified';
    }

    /**
     * Business Logic Methods
     */
    public function generateTrackingNumber(): string
    {
        $prefix = 'TRK';
        $year = date('Y');
        $month = date('m');
        
        // Get the next sequence number for this month
        $lastShipment = self::where('tracking_number', 'like', "{$prefix}{$year}{$month}%")
                           ->orderBy('tracking_number', 'desc')
                           ->first();
        
        if ($lastShipment) {
            $lastSequence = (int) substr($lastShipment->tracking_number, -6);
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

    public function updateStatus(string $newStatus, string $location = null, string $notes = null): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;
        
        if ($location) {
            $this->current_location = $location;
        }

        // Add tracking update
        $this->addTrackingUpdate($newStatus, $location, $notes);

        // Set actual arrival date if delivered
        if ($newStatus === self::STATUS_DELIVERED && !$this->actual_arrival) {
            $this->actual_arrival = now();
        }

        if ($this->save()) {
            ActivityLog::logActivity(
                'status_changed',
                self::class,
                $this->id,
                [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'location' => $location,
                    'notes' => $notes
                ]
            );
            
            return true;
        }
        
        return false;
    }

    public function addTrackingUpdate(string $status, string $location = null, string $notes = null): void
    {
        $updates = $this->tracking_updates ?? [];
        
        $updates[] = [
            'timestamp' => now()->toISOString(),
            'status' => $status,
            'location' => $location,
            'notes' => $notes,
            'updated_by' => auth()->id(),
        ];
        
        $this->tracking_updates = $updates;
    }

    public function getLatestTrackingUpdate(): ?array
    {
        if (!$this->tracking_updates || empty($this->tracking_updates)) {
            return null;
        }
        
        return end($this->tracking_updates);
    }

    public function detectDelay(): bool
    {
        if ($this->is_delayed && $this->status !== self::STATUS_DELAYED) {
            $this->updateStatus(self::STATUS_DELAYED, $this->current_location, 'Automatic delay detection');
            return true;
        }
        
        return false;
    }

    public function getDelayReasons(): array
    {
        $reasons = [];
        
        if ($this->is_delayed) {
            $delayDays = $this->days_delayed;
            
            if ($delayDays <= 3) {
                $reasons[] = 'Minor shipping delay';
            } elseif ($delayDays <= 7) {
                $reasons[] = 'Weather or port congestion';
            } elseif ($delayDays <= 14) {
                $reasons[] = 'Customs processing delay';
            } else {
                $reasons[] = 'Major logistical issues';
            }
        }
        
        return $reasons;
    }

    public function getSuggestedActions(): array
    {
        $actions = [];
        
        if ($this->is_delayed) {
            $actions[] = 'Contact carrier for updated ETA';
            $actions[] = 'Notify customer of delay';
            
            if ($this->days_delayed > 7) {
                $actions[] = 'Escalate to management';
                $actions[] = 'Consider compensation options';
            }
        }
        
        if ($this->status === self::STATUS_CUSTOMS) {
            $actions[] = 'Verify customs documentation';
            $actions[] = 'Check for additional fees';
        }
        
        return $actions;
    }

    public function updateEstimatedArrival(Carbon $newDate, string $reason = null): bool
    {
        $oldDate = $this->estimated_arrival;
        $this->estimated_arrival = $newDate;
        
        if ($this->save()) {
            $this->addTrackingUpdate(
                'eta_updated',
                $this->current_location,
                "ETA updated from {$oldDate} to {$newDate}. Reason: {$reason}"
            );
            
            ActivityLog::logActivity(
                'eta_updated',
                self::class,
                $this->id,
                [
                    'old_eta' => $oldDate,
                    'new_eta' => $newDate,
                    'reason' => $reason
                ]
            );
            
            return true;
        }
        
        return false;
    }

    public function getTrackingHistory(): array
    {
        $history = [];
        
        // Add creation event
        $history[] = [
            'timestamp' => $this->created_at->toISOString(),
            'status' => 'created',
            'location' => $this->departure_port,
            'notes' => 'Shipment created',
        ];
        
        // Add tracking updates
        if ($this->tracking_updates) {
            $history = array_merge($history, $this->tracking_updates);
        }
        
        // Sort by timestamp
        usort($history, function ($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });
        
        return $history;
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'booking_id' => 'required|exists:bookings,id',
            'carrier_name' => 'nullable|string|max:100',
            'vessel_name' => 'nullable|string|max:100',
            'container_number' => 'nullable|string|max:50',
            'current_location' => 'nullable|string|max:255',
            'status' => 'required|in:' . implode(',', self::VALID_STATUSES),
            'departure_port' => 'nullable|string|max:100',
            'arrival_port' => 'nullable|string|max:100',
            'departure_date' => 'nullable|date',
            'estimated_arrival' => 'nullable|date|after:departure_date',
            'actual_arrival' => 'nullable|date|after:departure_date',
            'tracking_updates' => 'nullable|array',
        ];
    }

    public static function updateValidationRules(): array
    {
        $rules = self::validationRules();
        
        // Make booking_id optional for updates
        $rules['booking_id'] = 'sometimes|' . $rules['booking_id'];
        $rules['status'] = 'sometimes|' . $rules['status'];
        
        return $rules;
    }
}