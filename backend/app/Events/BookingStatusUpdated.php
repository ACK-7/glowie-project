<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Booking Status Updated Event
 * 
 * Broadcasts real-time updates when booking status changes
 * (pending -> confirmed -> in_transit -> delivered)
 */
class BookingStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $booking;
    public $previousStatus;
    public $newStatus;
    public $updatedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Booking $booking, string $previousStatus, string $newStatus, $updatedBy = null)
    {
        $this->booking = $booking;
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
            // Customer channel - for the booking owner
            new PrivateChannel('customer.' . $this->booking->customer_id),
            
            // Admin channel - for all admin users
            new PrivateChannel('admin.bookings'),
            
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
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'customer_id' => $this->booking->customer_id,
            'customer_name' => $this->booking->customer->full_name ?? 'Unknown',
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'status_label' => $this->booking->status_label,
            'total_amount' => $this->booking->total_amount,
            'paid_amount' => $this->booking->paid_amount,
            'currency' => $this->booking->currency,
            'progress_percentage' => $this->booking->progress_percentage,
            'pickup_date' => $this->booking->pickup_date?->toISOString(),
            'delivery_date' => $this->booking->delivery_date?->toISOString(),
            'estimated_delivery' => $this->booking->estimated_delivery?->toISOString(),
            'updated_by' => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name ?? $this->updatedBy->full_name,
                'role' => $this->updatedBy->role ?? 'customer',
            ] : null,
            'updated_at' => $this->booking->updated_at->toISOString(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'booking.status.updated';
    }
}