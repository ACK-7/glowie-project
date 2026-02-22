<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Booking;
use App\Models\ActivityLog;
use App\Repositories\Contracts\ShipmentRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Shipment Service
 * 
 * Handles all business logic related to shipment management including
 * tracking, status transitions, delay detection, and automated workflows.
 */
class ShipmentService
{
    public function __construct(
        private ShipmentRepositoryInterface $shipmentRepository,
        private NotificationService $notificationService
    ) {}

    /**
     * Create a new shipment with validation and workflow
     */
    public function createShipment(array $data): Shipment
    {
        DB::beginTransaction();
        
        try {
            // Validate shipment data
            $this->validateShipmentData($data);
            
            // Set default values
            $data['status'] = $data['status'] ?? Shipment::STATUS_PREPARING;
            
            // Create the shipment
            $shipment = $this->shipmentRepository->create($data);
            
            // Update booking status if needed
            if ($shipment->booking && $shipment->booking->status === Booking::STATUS_CONFIRMED) {
                $shipment->booking->updateStatus(Booking::STATUS_IN_TRANSIT, 'Shipment created and in preparation');
            }
            
            // Generate notifications
            $this->notificationService->sendShipmentCreatedNotification($shipment);
            
            // Create audit trail
            $this->createAuditTrail('shipment_created', $shipment, [
                'action' => 'Shipment created',
                'user_id' => auth()->id(),
                'shipment_data' => $data
            ]);
            
            DB::commit();
            
            Log::info('Shipment created successfully', [
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'booking_id' => $shipment->booking_id
            ]);
            
            return $shipment;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create shipment', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update shipment status with validation and workflow triggers
     */
    public function updateShipmentStatus(int $shipmentId, string $newStatus, string $location = null, string $notes = null): Shipment
    {
        DB::beginTransaction();
        
        try {
            $shipment = $this->shipmentRepository->findOrFail($shipmentId);
            $oldStatus = $shipment->status;
            
            // Validate status transition
            if (!$shipment->canTransitionTo($newStatus)) {
                throw new Exception("Invalid status transition from {$oldStatus} to {$newStatus}");
            }
            
            // Update status
            $shipment->updateStatus($newStatus, $location, $notes);
            
            // Handle status-specific workflows
            $this->handleStatusWorkflow($shipment, $oldStatus, $newStatus, $location, $notes);
            
            // Generate notifications
            $this->notificationService->sendShipmentStatusUpdateNotification($shipment, $oldStatus, $newStatus, $location);
            
            // Create audit trail
            $this->createAuditTrail('shipment_status_updated', $shipment, [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'location' => $location,
                'notes' => $notes,
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            
            Log::info('Shipment status updated', [
                'shipment_id' => $shipment->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'location' => $location
            ]);
            
            return $shipment;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update shipment status', [
                'shipment_id' => $shipmentId,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update shipment with comprehensive validation
     */
    public function updateShipment(int $shipmentId, array $data): Shipment
    {
        DB::beginTransaction();
        
        try {
            $shipment = $this->shipmentRepository->findOrFail($shipmentId);
            $originalData = $shipment->toArray();
            
            // Validate update data
            $this->validateShipmentUpdateData($shipment, $data);
            
            // Update the shipment
            $shipment = $this->shipmentRepository->update($shipmentId, $data);
            
            // Generate notifications if significant changes
            if ($this->hasSignificantChanges($originalData, $data)) {
                $this->notificationService->sendShipmentUpdatedNotification($shipment, $originalData, $data);
            }
            
            // Create audit trail
            $this->createAuditTrail('shipment_updated', $shipment, [
                'changes' => array_diff_assoc($data, $originalData),
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            
            Log::info('Shipment updated successfully', [
                'shipment_id' => $shipment->id,
                'changes' => array_keys($data)
            ]);
            
            return $shipment;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update shipment', [
                'shipment_id' => $shipmentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update estimated arrival with workflow
     */
    public function updateEstimatedArrival(int $shipmentId, Carbon $newDate, string $reason = null): Shipment
    {
        DB::beginTransaction();
        
        try {
            $shipment = $this->shipmentRepository->findOrFail($shipmentId);
            $oldDate = $shipment->estimated_arrival;
            
            // Update estimated arrival
            $shipment->updateEstimatedArrival($newDate, $reason);
            
            // Check if this creates a delay
            if ($newDate->isFuture() && $oldDate && $newDate->gt($oldDate)) {
                $this->handleDelayDetected($shipment, $oldDate, $newDate, $reason);
            }
            
            // Generate notifications
            $this->notificationService->sendEstimatedArrivalUpdatedNotification($shipment, $oldDate, $newDate, $reason);
            
            // Create audit trail
            $this->createAuditTrail('estimated_arrival_updated', $shipment, [
                'old_eta' => $oldDate,
                'new_eta' => $newDate,
                'reason' => $reason,
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            
            Log::info('Shipment ETA updated', [
                'shipment_id' => $shipment->id,
                'old_eta' => $oldDate,
                'new_eta' => $newDate,
                'reason' => $reason
            ]);
            
            return $shipment;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update shipment ETA', [
                'shipment_id' => $shipmentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process delayed shipments automatically
     */
    public function processDelayedShipments(): array
    {
        $delayedShipments = $this->shipmentRepository->getOverdue();
        $processed = [];
        
        foreach ($delayedShipments as $shipment) {
            if ($shipment->detectDelay()) {
                $this->handleDelayDetected($shipment, $shipment->estimated_arrival, now(), 'Automatic delay detection');
                $processed[] = $shipment->id;
                
                Log::info('Shipment automatically marked as delayed', [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'days_delayed' => $shipment->days_delayed
                ]);
            }
        }
        
        return $processed;
    }

    /**
     * Get shipment tracking information
     */
    public function getShipmentTracking(string $trackingNumber): array
    {
        $shipment = $this->shipmentRepository->findByTrackingNumber($trackingNumber);
        
        if (!$shipment) {
            throw new Exception('Shipment not found with tracking number: ' . $trackingNumber);
        }
        
        return [
            'shipment' => $shipment,
            'tracking_history' => $shipment->getTrackingHistory(),
            'current_status' => [
                'status' => $shipment->status,
                'status_label' => $shipment->status_label,
                'location' => $shipment->current_location,
                'progress_percentage' => $shipment->progress_percentage,
                'estimated_days_remaining' => $shipment->estimated_days_remaining,
            ],
            'route_info' => [
                'departure_port' => $shipment->departure_port,
                'arrival_port' => $shipment->arrival_port,
                'route_description' => $shipment->route_description,
            ],
            'dates' => [
                'departure_date' => $shipment->departure_date,
                'estimated_arrival' => $shipment->estimated_arrival,
                'actual_arrival' => $shipment->actual_arrival,
            ],
            'carrier_info' => [
                'carrier_name' => $shipment->carrier_name,
                'vessel_name' => $shipment->vessel_name,
                'container_number' => $shipment->container_number,
            ],
            'delay_info' => [
                'is_delayed' => $shipment->is_delayed,
                'days_delayed' => $shipment->days_delayed,
                'delay_reasons' => $shipment->getDelayReasons(),
                'suggested_actions' => $shipment->getSuggestedActions(),
            ]
        ];
    }

    /**
     * Get shipments requiring attention
     */
    public function getShipmentsRequiringAttention(): Collection
    {
        return $this->shipmentRepository->getRequiringAttention();
    }

    /**
     * Get shipment statistics and analytics
     */
    public function getShipmentStatistics(): array
    {
        $stats = $this->shipmentRepository->getShipmentStatistics();
        
        // Add additional analytics
        $stats['delivery_performance'] = $this->shipmentRepository->getDeliveryPerformanceMetrics();
        $stats['carrier_performance'] = $this->shipmentRepository->getCarrierPerformanceAnalysis();
        $stats['shipments_requiring_attention'] = $this->getShipmentsRequiringAttention()->count();
        
        return $stats;
    }

    /**
     * Handle automated workflows based on status changes
     */
    private function handleStatusWorkflow(Shipment $shipment, string $oldStatus, string $newStatus, string $location = null, string $notes = null): void
    {
        switch ($newStatus) {
            case Shipment::STATUS_IN_TRANSIT:
                $this->handleShipmentInTransit($shipment);
                break;
                
            case Shipment::STATUS_CUSTOMS:
                $this->handleShipmentInCustoms($shipment);
                break;
                
            case Shipment::STATUS_DELIVERED:
                $this->handleShipmentDelivered($shipment);
                break;
                
            case Shipment::STATUS_DELAYED:
                $this->handleShipmentDelayed($shipment, $notes);
                break;
        }
    }

    /**
     * Handle shipment in transit workflow
     */
    private function handleShipmentInTransit(Shipment $shipment): void
    {
        // Update booking status
        if ($shipment->booking && $shipment->booking->status !== Booking::STATUS_IN_TRANSIT) {
            $shipment->booking->updateStatus(Booking::STATUS_IN_TRANSIT, 'Shipment is now in transit');
        }
        
        // Send tracking information to customer
        $this->notificationService->sendTrackingInformationNotification($shipment->booking);
    }

    /**
     * Handle shipment in customs workflow
     */
    private function handleShipmentInCustoms(Shipment $shipment): void
    {
        // Check for required customs documents
        $booking = $shipment->booking;
        if ($booking) {
            $customsDocuments = $booking->documents()->whereIn('document_type', ['customs', 'invoice'])->get();
            if ($customsDocuments->isEmpty()) {
                $this->notificationService->sendCustomsDocumentsRequiredNotification($booking);
            }
        }
    }

    /**
     * Handle shipment delivered workflow
     */
    private function handleShipmentDelivered(Shipment $shipment): void
    {
        // Update booking status
        if ($shipment->booking && $shipment->booking->status !== Booking::STATUS_DELIVERED) {
            $shipment->booking->updateStatus(Booking::STATUS_DELIVERED, 'Shipment has been delivered');
        }
        
        // Send delivery confirmation
        $this->notificationService->sendDeliveryConfirmationNotification($shipment);
    }

    /**
     * Handle shipment delayed workflow
     */
    private function handleShipmentDelayed(Shipment $shipment, string $notes = null): void
    {
        // Send delay notification
        $this->notificationService->sendShipmentDelayedNotification($shipment, $notes);
        
        // Escalate if delay is significant
        if ($shipment->days_delayed > 7) {
            $this->notificationService->sendDelayEscalationNotification($shipment);
        }
    }

    /**
     * Handle delay detection
     */
    private function handleDelayDetected(Shipment $shipment, Carbon $originalEta, Carbon $newEta, string $reason = null): void
    {
        $delayDays = $originalEta->diffInDays($newEta);
        
        // Update status to delayed if not already
        if ($shipment->status !== Shipment::STATUS_DELAYED) {
            $shipment->updateStatus(Shipment::STATUS_DELAYED, $shipment->current_location, "Delay detected: {$reason}");
        }
        
        // Send delay notifications
        $this->notificationService->sendDelayDetectedNotification($shipment, $delayDays, $reason);
        
        Log::warning('Shipment delay detected', [
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'original_eta' => $originalEta,
            'new_eta' => $newEta,
            'delay_days' => $delayDays,
            'reason' => $reason
        ]);
    }

    /**
     * Validate shipment data for creation
     */
    private function validateShipmentData(array $data): void
    {
        // Check booking exists
        if (!isset($data['booking_id'])) {
            throw new Exception('Booking ID is required');
        }
        
        $booking = Booking::find($data['booking_id']);
        if (!$booking) {
            throw new Exception('Invalid booking ID');
        }
        
        // Check if shipment already exists for booking
        if ($booking->shipment) {
            throw new Exception('Shipment already exists for this booking');
        }
        
        // Validate dates
        if (isset($data['departure_date']) && isset($data['estimated_arrival'])) {
            if (strtotime($data['estimated_arrival']) <= strtotime($data['departure_date'])) {
                throw new Exception('Estimated arrival must be after departure date');
            }
        }
    }

    /**
     * Validate shipment data for updates
     */
    private function validateShipmentUpdateData(Shipment $shipment, array $data): void
    {
        // Prevent updates to delivered shipments
        if ($shipment->status === Shipment::STATUS_DELIVERED) {
            throw new Exception('Cannot update delivered shipment');
        }
        
        // Validate date changes
        if (isset($data['departure_date']) || isset($data['estimated_arrival'])) {
            $departureDate = $data['departure_date'] ?? $shipment->departure_date;
            $estimatedArrival = $data['estimated_arrival'] ?? $shipment->estimated_arrival;
            
            if ($departureDate && $estimatedArrival && strtotime($estimatedArrival) <= strtotime($departureDate)) {
                throw new Exception('Estimated arrival must be after departure date');
            }
        }
    }

    /**
     * Check if changes are significant enough to notify
     */
    private function hasSignificantChanges(array $original, array $changes): bool
    {
        $significantFields = [
            'estimated_arrival', 'departure_date', 'carrier_name', 'vessel_name'
        ];
        
        foreach ($significantFields as $field) {
            if (isset($changes[$field]) && $changes[$field] !== $original[$field]) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Create audit trail entry
     */
    private function createAuditTrail(string $action, Shipment $shipment, array $details): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => Shipment::class,
            'model_id' => $shipment->id,
            'changes' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}