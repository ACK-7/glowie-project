<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dashboard Stats Updated Event
 * 
 * Broadcasts real-time updates to admin dashboard statistics
 * Triggered when key metrics change (new bookings, payments, etc.)
 */
class DashboardStatsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stats;
    public $changedMetrics;
    public $triggerEvent;

    /**
     * Create a new event instance.
     */
    public function __construct(array $stats, array $changedMetrics = [], ?string $triggerEvent = null)
    {
        $this->stats = $stats;
        $this->changedMetrics = $changedMetrics;
        $this->triggerEvent = $triggerEvent;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Global admin channel - for all admin dashboard users
            new PrivateChannel('admin.dashboard'),
            
            // Admin analytics channel - for analytics pages
            new PrivateChannel('admin.analytics'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'stats' => $this->stats,
            'changed_metrics' => $this->changedMetrics,
            'trigger_event' => $this->triggerEvent,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'dashboard.stats.updated';
    }
}