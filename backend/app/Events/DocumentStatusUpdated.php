<?php

namespace App\Events;

use App\Models\Document;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Document Status Updated Event
 * 
 * Broadcasts real-time updates when document verification status changes
 * (pending -> approved -> rejected -> expired)
 */
class DocumentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $document;
    public $previousStatus;
    public $newStatus;
    public $verifiedBy;
    public $rejectionReason;

    /**
     * Create a new event instance.
     */
    public function __construct(Document $document, string $previousStatus, string $newStatus, $verifiedBy = null, ?string $rejectionReason = null)
    {
        $this->document = $document;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->verifiedBy = $verifiedBy;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Customer channel - for the document owner
            new PrivateChannel('customer.' . $this->document->customer_id),
            
            // Admin channel - for all admin users
            new PrivateChannel('admin.documents'),
            
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
            'document_id' => $this->document->id,
            'booking_id' => $this->document->booking_id,
            'booking_reference' => $this->document->booking->booking_reference ?? null,
            'customer_id' => $this->document->customer_id,
            'customer_name' => $this->document->customer->full_name ?? 'Unknown',
            'document_type' => $this->document->document_type,
            'document_type_label' => $this->document->document_type_label,
            'file_name' => $this->document->file_name,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'status_label' => $this->document->status_label,
            'rejection_reason' => $this->rejectionReason,
            'expiry_date' => $this->document->expiry_date?->toISOString(),
            'is_expired' => $this->document->is_expired,
            'days_until_expiry' => $this->document->days_until_expiry,
            'verified_by' => $this->verifiedBy ? [
                'id' => $this->verifiedBy->id,
                'name' => $this->verifiedBy->name ?? $this->verifiedBy->full_name,
                'role' => $this->verifiedBy->role ?? 'admin',
            ] : null,
            'verified_at' => $this->document->verified_at?->toISOString(),
            'updated_at' => $this->document->updated_at->toISOString(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'document.status.updated';
    }
}