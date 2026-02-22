<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Payment Status Updated Event
 * 
 * Broadcasts real-time updates when payment status changes
 * (pending -> completed -> failed -> refunded)
 */
class PaymentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;
    public $previousStatus;
    public $newStatus;
    public $updatedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Payment $payment, string $previousStatus, string $newStatus, $updatedBy = null)
    {
        $this->payment = $payment;
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
            // Customer channel - for the payment owner
            new PrivateChannel('customer.' . $this->payment->customer_id),
            
            // Admin channel - for all admin users
            new PrivateChannel('admin.payments'),
            
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
            'payment_id' => $this->payment->id,
            'payment_reference' => $this->payment->payment_reference,
            'booking_id' => $this->payment->booking_id,
            'booking_reference' => $this->payment->booking->booking_reference ?? null,
            'customer_id' => $this->payment->customer_id,
            'customer_name' => $this->payment->customer->full_name ?? 'Unknown',
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'status_label' => $this->payment->status_label,
            'amount' => $this->payment->amount,
            'currency' => $this->payment->currency,
            'payment_method' => $this->payment->payment_method,
            'payment_gateway' => $this->payment->payment_gateway,
            'transaction_id' => $this->payment->transaction_id,
            'payment_date' => $this->payment->payment_date?->toISOString(),
            'updated_by' => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name ?? $this->updatedBy->full_name,
                'role' => $this->updatedBy->role ?? 'system',
            ] : null,
            'updated_at' => $this->payment->updated_at->toISOString(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'payment.status.updated';
    }
}