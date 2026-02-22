<?php

namespace App\Repositories;

use App\Models\Shipment;
use App\Repositories\Contracts\ShipmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Shipment Repository Implementation
 */
class ShipmentRepository extends BaseRepository implements ShipmentRepositoryInterface
{
    public function __construct(Shipment $model)
    {
        parent::__construct($model);
    }

    /**
     * Get shipments by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->byStatus($status)->get();
    }

    /**
     * Get active shipments
     */
    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * Get shipments in transit
     */
    public function getInTransit(): Collection
    {
        return $this->model->inTransit()->get();
    }

    /**
     * Get delayed shipments
     */
    public function getDelayed(): Collection
    {
        return $this->model->delayed()->get();
    }

    /**
     * Get overdue shipments
     */
    public function getOverdue(): Collection
    {
        return $this->model->overdue()->get();
    }

    /**
     * Get shipments by carrier
     */
    public function getByCarrier(string $carrier): Collection
    {
        return $this->model->byCarrier($carrier)->get();
    }

    /**
     * Get shipments by port
     */
    public function getByPort(string $port, string $type = 'both'): Collection
    {
        return $this->model->byPort($port, $type)->get();
    }

    /**
     * Find shipment by tracking number
     */
    public function findByTrackingNumber(string $trackingNumber): ?Shipment
    {
        return $this->model->where('tracking_number', $trackingNumber)->first();
    }

    /**
     * Get shipment statistics
     */
    public function getShipmentStatistics(): array
    {
        $totalShipments = $this->model->count();
        
        return [
            'total_shipments' => $totalShipments,
            'preparing_shipments' => $this->model->where('status', Shipment::STATUS_PREPARING)->count(),
            'in_transit_shipments' => $this->model->where('status', Shipment::STATUS_IN_TRANSIT)->count(),
            'customs_shipments' => $this->model->where('status', Shipment::STATUS_CUSTOMS)->count(),
            'delivered_shipments' => $this->model->where('status', Shipment::STATUS_DELIVERED)->count(),
            'delayed_shipments' => $this->model->where('status', Shipment::STATUS_DELAYED)->count(),
            'overdue_shipments' => $this->model->overdue()->count(),
            'active_shipments' => $this->model->active()->count(),
            'average_transit_time' => $this->getAverageTransitTime(),
            'on_time_delivery_rate' => $this->getOnTimeDeliveryRate(),
        ];
    }

    /**
     * Get delivery performance metrics
     */
    public function getDeliveryPerformanceMetrics(): array
    {
        $deliveredShipments = $this->model
            ->where('status', Shipment::STATUS_DELIVERED)
            ->whereNotNull('actual_arrival')
            ->whereNotNull('estimated_arrival')
            ->get();

        $onTimeDeliveries = $deliveredShipments->filter(function ($shipment) {
            return $shipment->actual_arrival <= $shipment->estimated_arrival;
        });

        $earlyDeliveries = $deliveredShipments->filter(function ($shipment) {
            return $shipment->actual_arrival < $shipment->estimated_arrival;
        });

        $lateDeliveries = $deliveredShipments->filter(function ($shipment) {
            return $shipment->actual_arrival > $shipment->estimated_arrival;
        });

        return [
            'total_delivered' => $deliveredShipments->count(),
            'on_time_deliveries' => $onTimeDeliveries->count(),
            'early_deliveries' => $earlyDeliveries->count(),
            'late_deliveries' => $lateDeliveries->count(),
            'on_time_rate' => $deliveredShipments->count() > 0 ? 
                ($onTimeDeliveries->count() / $deliveredShipments->count()) * 100 : 0,
            'average_delay_days' => $lateDeliveries->avg(function ($shipment) {
                return $shipment->actual_arrival->diffInDays($shipment->estimated_arrival);
            }) ?: 0,
            'average_early_days' => $earlyDeliveries->avg(function ($shipment) {
                return $shipment->estimated_arrival->diffInDays($shipment->actual_arrival);
            }) ?: 0,
        ];
    }

    /**
     * Get shipments requiring attention
     */
    public function getRequiringAttention(): Collection
    {
        return $this->model
            ->with(['booking.customer'])
            ->where(function ($query) {
                $query->where('status', Shipment::STATUS_DELAYED)
                      ->orWhere(function ($q) {
                          $q->where('estimated_arrival', '<', now())
                            ->whereNotIn('status', [Shipment::STATUS_DELIVERED]);
                      })
                      ->orWhere(function ($q) {
                          $q->where('status', Shipment::STATUS_CUSTOMS)
                            ->where('updated_at', '<', now()->subDays(3));
                      });
            })
            ->orderBy('estimated_arrival')
            ->get();
    }

    /**
     * Get shipment trends
     */
    public function getShipmentTrends(int $days = 30): Collection
    {
        return $this->model
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as shipments_count'),
                'status'
            ])
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(['date', 'status'])
            ->orderBy('date')
            ->get();
    }

    /**
     * Get carrier performance analysis
     */
    public function getCarrierPerformanceAnalysis(): Collection
    {
        return $this->model
            ->select([
                'carrier_name',
                DB::raw('COUNT(*) as total_shipments'),
                DB::raw('COUNT(CASE WHEN status = "delivered" THEN 1 END) as delivered_shipments'),
                DB::raw('COUNT(CASE WHEN status = "delayed" THEN 1 END) as delayed_shipments'),
                DB::raw('AVG(CASE WHEN status = "delivered" AND actual_arrival IS NOT NULL AND estimated_arrival IS NOT NULL 
                         THEN DATEDIFF(actual_arrival, estimated_arrival) END) as avg_delay_days'),
                DB::raw('COUNT(CASE WHEN status = "delivered" AND actual_arrival <= estimated_arrival THEN 1 END) as on_time_deliveries')
            ])
            ->whereNotNull('carrier_name')
            ->groupBy('carrier_name')
            ->orderBy('total_shipments', 'desc')
            ->get()
            ->map(function ($carrier) {
                $carrier->on_time_rate = $carrier->delivered_shipments > 0 ? 
                    ($carrier->on_time_deliveries / $carrier->delivered_shipments) * 100 : 0;
                $carrier->delay_rate = $carrier->total_shipments > 0 ? 
                    ($carrier->delayed_shipments / $carrier->total_shipments) * 100 : 0;
                return $carrier;
            });
    }

    /**
     * Search shipments
     */
    public function searchShipments(string $query): Collection
    {
        return $this->model
            ->with(['booking.customer', 'booking.vehicle'])
            ->where(function ($q) use ($query) {
                $q->where('tracking_number', 'LIKE', "%{$query}%")
                  ->orWhere('carrier_name', 'LIKE', "%{$query}%")
                  ->orWhere('vessel_name', 'LIKE', "%{$query}%")
                  ->orWhere('container_number', 'LIKE', "%{$query}%")
                  ->orWhere('current_location', 'LIKE', "%{$query}%")
                  ->orWhereHas('booking', function ($bookingQuery) use ($query) {
                      $bookingQuery->where('booking_reference', 'LIKE', "%{$query}%")
                                  ->orWhereHas('customer', function ($customerQuery) use ($query) {
                                      $customerQuery->where('first_name', 'LIKE', "%{$query}%")
                                               ->orWhere('last_name', 'LIKE', "%{$query}%");
                                  });
                  });
            })
            ->get();
    }

    /**
     * Get recent shipments
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model
            ->with(['booking.customer', 'booking.vehicle'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get average transit time
     */
    private function getAverageTransitTime(): float
    {
        return $this->model
            ->where('status', Shipment::STATUS_DELIVERED)
            ->whereNotNull('departure_date')
            ->whereNotNull('actual_arrival')
            ->get()
            ->avg(function ($shipment) {
                return $shipment->departure_date->diffInDays($shipment->actual_arrival);
            }) ?: 0;
    }

    /**
     * Get on-time delivery rate
     */
    private function getOnTimeDeliveryRate(): float
    {
        $deliveredShipments = $this->model
            ->where('status', Shipment::STATUS_DELIVERED)
            ->whereNotNull('actual_arrival')
            ->whereNotNull('estimated_arrival')
            ->count();

        if ($deliveredShipments === 0) {
            return 0;
        }

        $onTimeDeliveries = $this->model
            ->where('status', Shipment::STATUS_DELIVERED)
            ->whereNotNull('actual_arrival')
            ->whereNotNull('estimated_arrival')
            ->whereRaw('actual_arrival <= estimated_arrival')
            ->count();

        return ($onTimeDeliveries / $deliveredShipments) * 100;
    }

    /**
     * Apply search filter for shipments
     */
    protected function applySearchFilter(Builder $query, string $searchTerm): void
    {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('tracking_number', 'LIKE', "%{$searchTerm}%")
              ->orWhere('carrier_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('vessel_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('container_number', 'LIKE', "%{$searchTerm}%")
              ->orWhereHas('booking', function ($bookingQuery) use ($searchTerm) {
                  $bookingQuery->where('booking_reference', 'LIKE', "%{$searchTerm}%");
              });
        });
    }

    /**
     * Apply custom filters for shipments
     */
    protected function applyCustomFilter(Builder $query, string $key, $value): void
    {
        switch ($key) {
            case 'carrier_name':
                $query->where('carrier_name', 'LIKE', "%{$value}%");
                break;
                
            case 'departure_port':
                $query->where('departure_port', 'LIKE', "%{$value}%");
                break;
                
            case 'arrival_port':
                $query->where('arrival_port', 'LIKE', "%{$value}%");
                break;
                
            case 'overdue':
                if ($value) {
                    $query->where('estimated_arrival', '<', now())
                          ->whereNotIn('status', [Shipment::STATUS_DELIVERED]);
                }
                break;
                
            case 'delayed':
                if ($value) {
                    $query->where('status', Shipment::STATUS_DELAYED);
                }
                break;
                
            case 'booking_id':
                $query->where('booking_id', $value);
                break;
        }
    }
}