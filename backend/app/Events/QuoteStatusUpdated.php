<?php

namespace App\Events;

use App\Models\Quote;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Quote Status Updated Event
 * 
 * Broadcasts real-time updates when quote status changes
 * (pending -> approved -> converted, etc.)
 */
class QuoteStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quote;
    public $previousStatus;
    public $newStatus;
    public $updatedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Quote $quote, string $previousStatus, string $newStatus, $updatedBy = null)
    {
        $this->quote = $quote;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->updatedBy = $updatedBy;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Customer channel - for the quote owner
            new PrivateChannel('customer.' . $this->quote->customer_id),
            
            // Admin channel - for all admin users
            new PrivateChannel('admin.quotes'),
            
            // Global admin channel - for dashboard updates
            new PrivateChannel('admin.dashboard'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'quote_id' => $this->quote->id,
            'quote_reference' => $this->quote->quote_reference,
            'customer_id' => $this->quote->customer_id,
            'customer_name' => $this->quote->customer->full_name ?? 'Unknown',
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'status_label' => $this->quote->status_label,
            'total_amount' => $this->quote->total_amount,
            'currency' => $this->quote->currency,
            'is_expired' => $this->quote->is_expired,
            'is_valid' => $this->quote->is_valid,
            'days_until_expiry' => $this->quote->days_until_expiry,
            'updated_by' => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name ?? $this->updatedBy->full_name,
                'role' => $this->updatedBy->role ?? 'customer',
            ] : null,
            'updated_at' => $this->quote->updated_at->toISOString(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'quote.status.updated';
    }
}