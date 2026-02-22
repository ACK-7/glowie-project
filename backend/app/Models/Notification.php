<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'type',
        'title',
        'message',
        'data',
        'channels',
        'is_read',
        'read_at',
        'sent_at',
        'priority',
    ];

    protected $casts = [
        'data' => 'array',
        'channels' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    // Type constants
    const TYPE_BOOKING_CREATED = 'booking_created';
    const TYPE_BOOKING_UPDATED = 'booking_updated';
    const TYPE_QUOTE_APPROVED = 'quote_approved';
    const TYPE_QUOTE_REJECTED = 'quote_rejected';
    const TYPE_SHIPMENT_UPDATE = 'shipment_update';
    const TYPE_DOCUMENT_REQUIRED = 'document_required';
    const TYPE_DOCUMENT_APPROVED = 'document_approved';
    const TYPE_DOCUMENT_REJECTED = 'document_rejected';
    const TYPE_PAYMENT_RECEIVED = 'payment_received';
    const TYPE_PAYMENT_OVERDUE = 'payment_overdue';
    const TYPE_SYSTEM_ALERT = 'system_alert';

    // Channel constants
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_PUSH = 'push';
    const CHANNEL_DATABASE = 'database';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Relationships
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scopes
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeUnsent($query)
    {
        return $query->whereNull('sent_at');
    }

    /**
     * Accessors
     */
    public function getIsReadAttribute(): bool
    {
        return !is_null($this->read_at);
    }

    public function getIsSentAttribute(): bool
    {
        return !is_null($this->sent_at);
    }

    /**
     * Business Logic Methods
     */
    public function markAsRead(): bool
    {
        $this->read_at = now();
        return $this->save();
    }

    public function markAsUnread(): bool
    {
        $this->read_at = null;
        return $this->save();
    }

    public function markAsSent(): bool
    {
        $this->sent_at = now();
        return $this->save();
    }

    /**
     * Static helper methods
     */
    public static function createForBooking(Booking $booking, string $type, string $title, string $message, array $data = []): self
    {
        return self::create([
            'notifiable_type' => Customer::class,
            'notifiable_id' => $booking->customer_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => array_merge($data, ['booking_id' => $booking->id]),
            'channel' => self::CHANNEL_DATABASE,
            'priority' => self::PRIORITY_NORMAL,
        ]);
    }

    public static function createForCustomer(Customer $customer, string $type, string $title, string $message, array $data = []): self
    {
        return self::create([
            'notifiable_type' => Customer::class,
            'notifiable_id' => $customer->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'channel' => self::CHANNEL_DATABASE,
            'priority' => self::PRIORITY_NORMAL,
        ]);
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'notifiable_type' => 'required|string',
            'notifiable_id' => 'required|integer',
            'type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'data' => 'nullable|array',
            'channel' => 'required|in:email,sms,push,database',
            'priority' => 'required|in:low,normal,high,urgent',
        ];
    }
}