<?php

namespace App\Repositories\Contracts;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Collection;

/**
 * Shipment Repository Interface
 */
interface ShipmentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get shipments by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get active shipments
     */
    public function getActive(): Collection;

    /**
     * Get shipments in transit
     */
    public function getInTransit(): Collection;

    /**
     * Get delayed shipments
     */
    public function getDelayed(): Collection;

    /**
     * Get overdue shipments
     */
    public function getOverdue(): Collection;

    /**
     * Get shipments by carrier
     */
    public function getByCarrier(string $carrier): Collection;

    /**
     * Get shipments by port
     */
    public function getByPort(string $port, string $type = 'both'): Collection;

    /**
     * Find shipment by tracking number
     */
    public function findByTrackingNumber(string $trackingNumber): ?Shipment;

    /**
     * Get shipment statistics
     */
    public function getShipmentStatistics(): array;

    /**
     * Get delivery performance metrics
     */
    public function getDeliveryPerformanceMetrics(): array;

    /**
     * Get shipments requiring attention
     */
    public function getRequiringAttention(): Collection;

    /**
     * Get shipment trends
     */
    public function getShipmentTrends(int $days = 30): Collection;

    /**
     * Get carrier performance analysis
     */
    public function getCarrierPerformanceAnalysis(): Collection;

    /**
     * Search shipments
     */
    public function searchShipments(string $query): Collection;

    /**
     * Get recent shipments
     */
    public function getRecent(int $limit = 10): Collection;
}