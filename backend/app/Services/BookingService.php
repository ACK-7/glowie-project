<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\ActivityLog;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Booking Service
 * 
 * Handles all business logic related to booking management including
 * status transitions, validations, workflows, notifications, and audit trails.
 */
class BookingService
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
        private NotificationService $notificationService
    ) {}

    /**
     * Create a new booking with comprehensive validation and workflow
     */
    public function createBooking(array $data): Booking
    {
        DB::beginTransaction();
        
        try {
            // Validate business rules
            $this->validateBookingData($data);
            
            // Set default values
            $data['created_by'] = auth()->id();
            $data['status'] = Booking::STATUS_PENDING;
            
            // Create the booking
            $booking = $this->bookingRepository->create($data);
            
            // Generate notifications
            $this->notificationService->sendBookingCreatedNotification($booking);
            
            // Create audit trail
            $this->createAuditTrail('booking_created', $booking, [
                'action' => 'Booking created',
                'user_id' => auth()->id(),
                'booking_data' => $data
            ]);
            
            DB::commit();
            
            Log::info('Booking created successfully', [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'customer_id' => $booking->customer_id
            ]);
            
            return $booking;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create booking', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update booking status with validation and workflow triggers
     */
    public function updateBookingStatus(int $bookingId, string $newStatus, string $reason = null): Booking
    {
        DB::beginTransaction();
        
        try {
            $booking = $this->bookingRepository->findOrFail($bookingId);
            $oldStatus = $booking->status;
            
            // Validate status transition
            if (!$booking->canTransitionTo($newStatus)) {
                throw new Exception("Invalid status transition from {$oldStatus} to {$newStatus}");
            }
            
            // Update status
            $booking->status = $newStatus;
            $booking->updated_by = auth()->id();
            $booking->save();
            
            // Handle status-specific workflows
            $this->handleStatusWorkflow($booking, $oldStatus, $newStatus, $reason);
            
            // Generate notifications
            $this->notificationService->sendBookingStatusUpdateNotification($booking, $oldStatus, $newStatus);
            
            // Create audit trail
            $this->createAuditTrail('status_updated', $booking, [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            
            Log::info('Booking status updated', [
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason
            ]);
            
            return $booking;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update booking status', [
                'booking_id' => $bookingId,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update booking with comprehensive validation
     */
    public function updateBooking(int $bookingId, array $data): Booking
    {
        DB::beginTransaction();
        
        try {
            $booking = $this->bookingRepository->findOrFail($bookingId);
            $originalData = $booking->toArray();
            
            // Validate business rules for updates
            $this->validateBookingUpdateData($booking, $data);
            
            // Set updated by
            $data['updated_by'] = auth()->id();
            
            // Update the booking
            $updated = $this->bookingRepository->update($bookingId, $data);
            
            if (!$updated) {
                throw new Exception('Failed to update booking');
            }
            
            // Refresh the booking to get updated data
            $booking = $this->bookingRepository->findOrFail($bookingId);
            
            // Generate notifications if significant changes
            if ($this->hasSignificantChanges($originalData, $data)) {
                $this->notificationService->sendBookingUpdatedNotification($booking, $originalData, $data);
            }
            
            // Create audit trail
            $this->createAuditTrail('booking_updated', $booking, [
                'changes' => array_diff_assoc($data, $originalData),
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            
            Log::info('Booking updated successfully', [
                'booking_id' => $booking->id,
                'changes' => array_keys($data)
            ]);
            
            return $booking;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update booking', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete booking with confirmation and audit trail
     */
    public function deleteBooking(int $bookingId, string $reason): bool
    {
        DB::beginTransaction();
        
        try {
            $booking = $this->bookingRepository->findOrFail($bookingId);
            if (!$this->canDeleteBooking($booking)) {
                throw new Exception('Booking cannot be deleted in current status: ' . $booking->status);
            }
            
            // Store booking data for audit
            $bookingData = $booking->toArray();
            
            // Generate notifications
            $this->notificationService->sendBookingDeletedNotification($booking, $reason);
            
            // Create audit trail before deletion
            $this->createAuditTrail('booking_deleted', $booking, [
                'reason' => $reason,
                'user_id' => auth()->id(),
                'booking_data' => $bookingData
            ]);
            
            // Delete the booking
            $deleted = $this->bookingRepository->delete($bookingId);
            
            DB::commit();
            
            Log::info('Booking deleted successfully', [
                'booking_id' => $bookingId,
                'booking_reference' => $bookingData['booking_reference'],
                'reason' => $reason
            ]);
            
            return $deleted;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete booking', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get bookings with advanced filtering
     */
    public function getBookingsWithFilters(array $filters): Collection
    {
        return $this->bookingRepository->getFilteredPaginated($filters);
    }

    /**
     * Get booking statistics and analytics
     */
    public function getBookingStatistics(array $filters = []): array
    {
        return $this->bookingRepository->getStatistics($filters);
    }

    /**
     * Get bookings requiring attention
     */
    public function getBookingsRequiringAttention(): Collection
    {
        return $this->bookingRepository->getRequiringAttention();
    }

    /**
     * Process payment for booking
     */
    public function processPayment(int $bookingId, float $amount, string $method = 'bank_transfer'): bool
    {
        DB::beginTransaction();
        
        try {
            $booking = $this->bookingRepository->findOrFail($bookingId);
            
            // Validate payment amount
            if ($amount <= 0 || $amount > $booking->balance_amount) {
                throw new Exception('Invalid payment amount');
            }
            
            // Add payment
            $payment = $booking->addPayment($amount, $method);
            
            // Check if booking is fully paid
            if ($booking->fresh()->payment_status === 'paid') {
                $this->notificationService->sendPaymentCompletedNotification($booking);
            }
            
            // Create audit trail
            $this->createAuditTrail('payment_processed', $booking, [
                'payment_id' => $payment->id,
                'amount' => $amount,
                'method' => $method,
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            
            Log::info('Payment processed for booking', [
                'booking_id' => $bookingId,
                'payment_id' => $payment->id,
                'amount' => $amount
            ]);
            
            return true;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to process payment', [
                'booking_id' => $bookingId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle automated workflows based on status changes
     */
    private function handleStatusWorkflow(Booking $booking, string $oldStatus, string $newStatus, string $reason = null): void
    {
        switch ($newStatus) {
            case Booking::STATUS_CONFIRMED:
                $this->handleBookingConfirmed($booking);
                break;
                
            case Booking::STATUS_IN_TRANSIT:
                $this->handleBookingInTransit($booking);
                break;
                
            case Booking::STATUS_DELIVERED:
                $this->handleBookingDelivered($booking);
                break;
                
            case Booking::STATUS_CANCELLED:
                $this->handleBookingCancelled($booking, $reason);
                break;
        }
    }

    /**
     * Handle booking confirmation workflow
     */
    private function handleBookingConfirmed(Booking $booking): void
    {
        // Create shipment if not exists
        if (!$booking->shipment) {
            $booking->shipment()->create([
                'tracking_number' => 'TRK' . time() . rand(1000, 9999),
                'status' => 'preparing',
                'departure_port' => $booking->route->origin_port ?? null,
                'arrival_port' => $booking->route->destination_port ?? null,
            ]);
        }
        
        // Check for missing documents
        $missingDocs = $booking->getMissingDocuments();
        if (!empty($missingDocs)) {
            $this->notificationService->sendMissingDocumentsNotification($booking, $missingDocs);
        }
    }

    /**
     * Handle booking in transit workflow
     */
    private function handleBookingInTransit(Booking $booking): void
    {
        // Update shipment status
        if ($booking->shipment) {
            $booking->shipment->updateStatus('in_transit');
        }
        
        // Send tracking information to customer
        $this->notificationService->sendTrackingInformationNotification($booking);
    }

    /**
     * Handle booking delivered workflow
     */
    private function handleBookingDelivered(Booking $booking): void
    {
        // Update shipment status
        if ($booking->shipment) {
            $booking->shipment->updateStatus('delivered');
        }
        
        // Update customer statistics
        $customer = $booking->customer;
        $customer->incrementBookingCount();
        $customer->addToTotalSpent($booking->total_amount);
        
        // Send completion notification
        $this->notificationService->sendBookingCompletedNotification($booking);
    }

    /**
     * Handle booking cancellation workflow
     */
    private function handleBookingCancelled(Booking $booking, string $reason = null): void
    {
        // Cancel shipment if exists
        if ($booking->shipment && $booking->shipment->status !== 'delivered') {
            $booking->shipment->updateStatus('cancelled');
        }
        
        // Process refunds if applicable
        if ($booking->paid_amount > 0) {
            $this->notificationService->sendRefundProcessingNotification($booking, $reason);
        }
    }

    /**
     * Validate booking data for creation
     */
    private function validateBookingData(array $data): void
    {
        // Check customer exists and is active
        $customer = Customer::find($data['customer_id']);
        if (!$customer || !$customer->is_active) {
            throw new Exception('Invalid or inactive customer');
        }
        
        // Validate quote if provided
        if (isset($data['quote_id'])) {
            $quote = Quote::find($data['quote_id']);
            if (!$quote || $quote->status !== Quote::STATUS_APPROVED || $quote->is_expired) {
                throw new Exception('Invalid or expired quote');
            }
        }
        
        // Validate dates
        if (isset($data['pickup_date']) && isset($data['delivery_date'])) {
            if (strtotime($data['delivery_date']) <= strtotime($data['pickup_date'])) {
                throw new Exception('Delivery date must be after pickup date');
            }
        }
        
        // Validate amounts
        if ($data['total_amount'] <= 0) {
            throw new Exception('Total amount must be greater than zero');
        }
        
        if (isset($data['paid_amount']) && $data['paid_amount'] > $data['total_amount']) {
            throw new Exception('Paid amount cannot exceed total amount');
        }
    }

    /**
     * Validate booking data for updates
     */
    private function validateBookingUpdateData(Booking $booking, array $data): void
    {
        // Allow admins to update any booking, but warn for delivered bookings
        // Regular users cannot update delivered or cancelled bookings
        $user = auth()->user();
        $isAdmin = $user && ($user->role === 'admin' || $user->role === 'super_admin');
        
        if (!$isAdmin && in_array($booking->status, [Booking::STATUS_DELIVERED, Booking::STATUS_CANCELLED])) {
            throw new Exception('Cannot update booking in ' . $booking->status . ' status');
        }
        
        // Validate date changes
        if (isset($data['pickup_date']) || isset($data['delivery_date'])) {
            $pickupDate = $data['pickup_date'] ?? $booking->pickup_date;
            $deliveryDate = $data['delivery_date'] ?? $booking->delivery_date;
            
            if ($pickupDate && $deliveryDate && strtotime($deliveryDate) <= strtotime($pickupDate)) {
                throw new Exception('Delivery date must be after pickup date');
            }
        }
        
        // Validate amount changes
        if (isset($data['total_amount']) && $data['total_amount'] < $booking->paid_amount) {
            throw new Exception('Total amount cannot be less than already paid amount');
        }
    }

    /**
     * Check if booking can be deleted
     */
    private function canDeleteBooking(Booking $booking): bool
    {
        // Cannot delete delivered bookings
        if ($booking->status === Booking::STATUS_DELIVERED) {
            return false;
        }
        
        // Cannot delete if shipment is in transit
        if ($booking->shipment && $booking->shipment->status === 'in_transit') {
            return false;
        }
        
        return true;
    }

    /**
     * Check if changes are significant enough to notify
     */
    private function hasSignificantChanges(array $original, array $changes): bool
    {
        $significantFields = [
            'pickup_date', 'delivery_date', 'estimated_delivery',
            'total_amount', 'recipient_name', 'recipient_phone'
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
    private function createAuditTrail(string $action, Booking $booking, array $details): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => Booking::class,
            'model_id' => $booking->id,
            'changes' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}