<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\ActivityLog;
use App\Repositories\Contracts\QuoteRepositoryInterface;
use App\Services\NotificationService;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Quote Service
 * 
 * Handles all business logic related to quote management including
 * creation, approval workflows, conversion to bookings, expiry management,
 * and automated notifications.
 */
class QuoteService
{
    public function __construct(
        private QuoteRepositoryInterface $quoteRepository,
        private NotificationService $notificationService,
        private BookingService $bookingService
    ) {}

    /**
     * Create a new quote with validation and workflow
     */
    public function createQuote(array $data): Quote
    {
        DB::beginTransaction();
        
        try {
            // Validate quote data
            $this->validateQuoteData($data);
            
            // Set default values
            $data['created_by'] = auth()->id();
            $data['status'] = Quote::STATUS_PENDING;
            
            // Calculate total amount if not provided
            if (!isset($data['total_amount'])) {
                $data['total_amount'] = $this->calculateTotalAmount($data);
            }
            
            // Set validity period if not provided
            if (!isset($data['valid_until'])) {
                $data['valid_until'] = now()->addDays(30);
            }
            
            // Create the quote
            $quote = $this->quoteRepository->create($data);
            
            // Generate notifications
            $this->notificationService->sendQuoteCreatedNotification($quote);
            
            // Create audit trail
            $this->createAuditTrail('quote_created', $quote, [
                'action' => 'Quote created',
                'user_id' => auth()->id(),
                'quote_data' => $data
            ]);
            
            DB::commit();
            
            Log::info('Quote created successfully', [
                'quote_id' => $quote->id,
                'quote_reference' => $quote->quote_reference,
                'customer_id' => $quote->customer_id
            ]);
            
            return $quote;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create quote', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Convert quote to booking with data integrity
     */
    public function convertQuoteToBooking(int $quoteId, array $additionalData = []): Booking
    {
        DB::beginTransaction();
        
        try {
            $quote = $this->quoteRepository->findOrFail($quoteId);
            
            // Validate conversion is allowed
            if ($quote->status !== Quote::STATUS_APPROVED) {
                throw new Exception('Only approved quotes can be converted to bookings');
            }
            
            if ($quote->is_expired) {
                throw new Exception('Cannot convert expired quote');
            }
            
            // Prepare booking data from quote
            $bookingData = $this->prepareBookingDataFromQuote($quote, $additionalData);
            
            // Create booking using BookingService
            $booking = $this->bookingService->createBooking($bookingData);
            
            // Update quote status to converted
            $quote->status = Quote::STATUS_CONVERTED;
            $quote->save();
            
            // Generate notifications
            $this->notificationService->sendQuoteConvertedNotification($quote, $booking);
            
            // Create audit trail
            $this->createAuditTrail('quote_converted', $quote, [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            
            Log::info('Quote converted to booking successfully', [
                'quote_id' => $quote->id,
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference
            ]);
            
            return $booking;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to convert quote to booking', [
                'quote_id' => $quoteId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Prepare booking data from quote
     */
    private function prepareBookingDataFromQuote(Quote $quote, array $additionalData): array
    {
        $bookingData = [
            'customer_id' => $quote->customer_id,
            'quote_id' => $quote->id,
            'route_id' => $quote->route_id,
            'total_amount' => $quote->total_amount,
            'currency' => $quote->currency,
            'notes' => $quote->notes,
        ];
        
        // Create vehicle from quote vehicle details if needed
        if ($quote->vehicle_details && !isset($additionalData['vehicle_id'])) {
            $vehicleData = is_array($quote->vehicle_details) ? $quote->vehicle_details : json_decode($quote->vehicle_details, true);
            
            // Ensure vehicle_type_id is set (default to 1 if not provided)
            if (!isset($vehicleData['vehicle_type_id'])) {
                // Try to find a default vehicle type or use ID 1
                $defaultVehicleType = \App\Models\VehicleType::first();
                $vehicleData['vehicle_type_id'] = $defaultVehicleType ? $defaultVehicleType->id : 1;
            }
            
            // Set default values for required fields
            $vehicleData['is_running'] = $vehicleData['is_running'] ?? true;
            
            $vehicle = \App\Models\Vehicle::create($vehicleData);
            $bookingData['vehicle_id'] = $vehicle->id;
        }
        
        // Merge additional data
        return array_merge($bookingData, $additionalData);
    }

    /**
     * Validate quote data for creation
     */
    private function validateQuoteData(array $data): void
    {
        // Check customer exists and is active
        $customer = Customer::find($data['customer_id']);
        if (!$customer || !$customer->is_active) {
            throw new Exception('Invalid or inactive customer');
        }
        
        // Validate vehicle details
        if (!isset($data['vehicle_details']) || !is_array($data['vehicle_details'])) {
            throw new Exception('Vehicle details are required');
        }
        
        $requiredVehicleFields = ['make', 'model', 'year'];
        foreach ($requiredVehicleFields as $field) {
            if (!isset($data['vehicle_details'][$field])) {
                throw new Exception("Vehicle {$field} is required");
            }
        }
        
        // Validate pricing
        if (!isset($data['base_price']) || $data['base_price'] <= 0) {
            throw new Exception('Base price must be greater than zero');
        }
    }

    /**
     * Calculate total amount from quote data
     */
    private function calculateTotalAmount(array $data): float
    {
        $total = $data['base_price'] ?? 0;
        
        if (isset($data['additional_fees']) && is_array($data['additional_fees'])) {
            foreach ($data['additional_fees'] as $fee) {
                $total += $fee['amount'] ?? 0;
            }
        }
        
        return $total;
    }

    /**
     * Create audit trail entry
     */
    private function createAuditTrail(string $action, Quote $quote, array $details): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => Quote::class,
            'model_id' => $quote->id,
            'changes' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}