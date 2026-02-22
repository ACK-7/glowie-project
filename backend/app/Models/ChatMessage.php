<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'customer_id',
        'user_id',
        'message',
        'sender_type',
        'is_read',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'is_read' => false,
        'sender_type' => 'customer',
    ];

    // Sender type constants
    const SENDER_CUSTOMER = 'customer';
    const SENDER_ADMIN = 'admin';
    const SENDER_SYSTEM = 'system';

    const VALID_SENDER_TYPES = [
        self::SENDER_CUSTOMER,
        self::SENDER_ADMIN,
        self::SENDER_SYSTEM,
    ];

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByBooking($query, $bookingId)
    {
        return $query->where('booking_id', $bookingId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeBySenderType($query, $senderType)
    {
        return $query->where('sender_type', $senderType);
    }

    /**
     * Accessors
     */
    public function getSenderNameAttribute(): string
    {
        switch ($this->sender_type) {
            case self::SENDER_CUSTOMER:
                return $this->customer ? $this->customer->full_name : 'Customer';
            case self::SENDER_ADMIN:
                return $this->user ? $this->user->full_name : 'Admin';
            case self::SENDER_SYSTEM:
                return 'System';
            default:
                return 'Unknown';
        }
    }

    /**
     * Business Logic Methods
     */
    public function markAsRead(): bool
    {
        $this->is_read = true;
        return $this->save();
    }

    public function markAsUnread(): bool
    {
        $this->is_read = false;
        return $this->save();
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'booking_id' => 'required|exists:bookings,id',
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'nullable|exists:users,id',
            'message' => 'required|string|max:2000',
            'sender_type' => 'required|in:' . implode(',', self::VALID_SENDER_TYPES),
            'is_read' => 'boolean',
            'metadata' => 'nullable|array',
        ];
    }
}