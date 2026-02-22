<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Shipment Location Updated Event
 * 
 * Broadcasts real-time updates when shipment location or status changes
 * Provides live tracking updates to customers and admins
 */
class ShipmentLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $shipment;
    public $previousLocation;
    public $newLocation;
    public $statusUpdate;
    public $updatedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Shipment $shipment, ?string $previousLocation, string $newLocation, ?string $statusUpdate = null, $updatedBy = null)
    {
        $this->shipment = $shipment;
        $this->previousLocation = $previousLocation;
        $this->newLocation = $newLocation;
        $this->statusUpdate = $statusUpdate;
        $this->updatedBy = $updatedBy;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Customer channel - for the shipment owner
            new PrivateChannel('customer.' . $this->shipment->booking->customer_id),
            
            // Public tracking channel - for anyone with tracking number
            new Channel('tracking.' . $this->shipment->tracking_number),
            
            // Admin channel - for all admin users
            new PrivateChannel('admin.shipments'),
            
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
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->shipment->tracking_number,
            'booking_id' => $this->shipment->booking_id,
            'booking_reference' => $this->shipment->booking->booking_reference ?? null,
            'customer_id' => $this->shipment->booking->customer_id ?? null,
            'customer_name' => $this->shipment->booking->customer->full_name ?? 'Unknown',
            'previous_location' => $this->previousLocation,
            'current_location' => $this->newLocation,
            'status' => $this->shipment->status,
            'status_label' => $this->shipment->status_label,
            'status_update' => $this->statusUpdate,
            'carrier_name' => $this->shipment->carrier_name,
            'vessel_name' => $this->shipment->vessel_name,
            'container_number' => $this->shipment->container_number,
            'departure_port' => $this->shipment->departure_port,
            'arrival_port' => $this->shipment->arrival_port,
            'departure_date' => $this->shipment->departure_date?->toISOString(),
            'estimated_arrival' => $this->shipment->estimated_arrival?->toISOString(),
            'actual_arrival' => $this->shipment->actual_arrival?->toISOString(),
            'tracking_updates' => $this->shipment->tracking_updates,
            'updated_by' => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name ?? $this->updatedBy->full_name,
                'role' => $this->updatedBy->role ?? 'system',
            ] : null,
            'updated_at' => $this->shipment->updated_at->toISOString(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'shipment.location.updated';
    }
}