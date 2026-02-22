<?php

namespace App\Services;

use App\Events\QuoteStatusUpdated;
use App\Events\BookingStatusUpdated;
use App\Events\ShipmentLocationUpdated;
use App\Events\PaymentStatusUpdated;
use App\Events\DocumentStatusUpdated;
use App\Events\DashboardStatsUpdated;
use App\Models\Quote;
use App\Models\Booking;
use App\Models\Shipment;
use App\Models\Payment;
use App\Models\Document;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Real-Time Service
 * 
 * Manages real-time broadcasting of events across the application
 * Provides centralized methods for triggering real-time updates
 */
class RealTimeService
{
    /**
     * Broadcast quote status update
     */
    public function broadcastQuoteStatusUpdate(Quote $quote, string $previousStatus, $updatedBy = null): void
    {
        try {
            event(new QuoteStatusUpdated($quote, $previousStatus, $quote->status, $updatedBy));
            
            // Update dashboard stats if quote was approved/rejected
            if (in_array($quote->status, ['approved', 'rejected'])) {
                $this->updateDashboardStats(['quotes'], 'quote.status.updated');
            }
            
            Log::info('Quote status update broadcasted', [
                'quote_id' => $quote->id,
                'previous_status' => $previousStatus,
                'new_status' => $quote->status,
                'updated_by' => $updatedBy?->id,
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to broadcast quote status update', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast booking status update
     */
    public function broadcastBookingStatusUpdate(Booking $booking, string $previousStatus, $updatedBy = null): void
    {
        try {
            event(new BookingStatusUpdated($booking, $previousStatus, $booking->status, $updatedBy));
            
            // Update dashboard stats for booking status changes
            $this->updateDashboardStats(['bookings'], 'booking.status.updated');
            
            Log::info('Booking status update broadcasted', [
                'booking_id' => $booking->id,
                'previous_status' => $previousStatus,
                'new_status' => $booking->status,
                'updated_by' => $updatedBy?->id,
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to broadcast booking status update', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast shipment location update
     */
    public function broadcastShipmentLocationUpdate(Shipment $shipment, ?string $previousLocation, ?string $statusUpdate = null, $updatedBy = null): void
    {
        try {
            event(new ShipmentLocationUpdated(
                $shipment, 
                $previousLocation, 
                $shipment->current_location, 
                $statusUpdate, 
                $updatedBy
            ));
            
            // Update dashboard stats for shipment updates
            $this->updateDashboardStats(['shipments'], 'shipment.location.updated');
            
            Log::info('Shipment location update broadcasted', [
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'previous_location' => $previousLocation,
                'current_location' => $shipment->current_location,
                'status_update' => $statusUpdate,
                'updated_by' => $updatedBy?->id,
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to broadcast shipment location update', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast payment status update
     */
    public function broadcastPaymentStatusUpdate(Payment $payment, string $previousStatus, $updatedBy = null): void
    {
        try {
            event(new PaymentStatusUpdated($payment, $previousStatus, $payment->status, $updatedBy));
            
            // Update dashboard stats for payment changes
            $this->updateDashboardStats(['payments', 'revenue'], 'payment.status.updated');
            
            Log::info('Payment status update broadcasted', [
                'payment_id' => $payment->id,
                'previous_status' => $previousStatus,
                'new_status' => $payment->status,
                'amount' => $payment->amount,
                'updated_by' => $updatedBy?->id,
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to broadcast payment status update', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast document status update
     */
    public function broadcastDocumentStatusUpdate(Document $document, string $previousStatus, $verifiedBy = null, ?string $rejectionReason = null): void
    {
        try {
            event(new DocumentStatusUpdated($document, $previousStatus, $document->status, $verifiedBy, $rejectionReason));
            
            // Update dashboard stats for document verification
            $this->updateDashboardStats(['documents'], 'document.status.updated');
            
            Log::info('Document status update broadcasted', [
                'document_id' => $document->id,
                'previous_status' => $previousStatus,
                'new_status' => $document->status,
                'verified_by' => $verifiedBy?->id,
                'rejection_reason' => $rejectionReason,
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to broadcast document status update', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update and broadcast dashboard statistics
     */
    public function updateDashboardStats(array $changedMetrics = [], ?string $triggerEvent = null): void
    {
        try {
            // Get fresh dashboard statistics
            $stats = $this->calculateDashboardStats();
            
            // Cache the stats for 5 minutes to avoid recalculation
            Cache::put('dashboard.stats', $stats, 300);
            
            // Broadcast the updated stats
            event(new DashboardStatsUpdated($stats, $changedMetrics, $triggerEvent));
            
            Log::info('Dashboard stats updated and broadcasted', [
                'changed_metrics' => $changedMetrics,
                'trigger_event' => $triggerEvent,
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to update dashboard stats', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate current dashboard statistics
     */
    private function calculateDashboardStats(): array
    {
        try {
            return [
                'quotes' => [
                    'total' => Quote::count(),
                    'pending' => Quote::where('status', 'pending')->count(),
                    'approved' => Quote::where('status', 'approved')->count(),
                    'expired' => Quote::where('valid_until', '<', now())->count(),
                    'today' => Quote::whereDate('created_at', today())->count(),
                ],
                'bookings' => [
                    'total' => Booking::count(),
                    'pending' => Booking::where('status', 'pending')->count(),
                    'confirmed' => Booking::where('status', 'confirmed')->count(),
                    'in_transit' => Booking::where('status', 'in_transit')->count(),
                    'delivered' => Booking::where('status', 'delivered')->count(),
                    'today' => Booking::whereDate('created_at', today())->count(),
                ],
                'shipments' => [
                    'total' => Shipment::count(),
                    'preparing' => Shipment::where('status', 'preparing')->count(),
                    'in_transit' => Shipment::where('status', 'in_transit')->count(),
                    'customs' => Shipment::where('status', 'customs')->count(),
                    'delivered' => Shipment::where('status', 'delivered')->count(),
                    'delayed' => Shipment::where('status', 'delayed')->count(),
                ],
                'payments' => [
                    'total' => Payment::count(),
                    'pending' => Payment::where('status', 'pending')->count(),
                    'completed' => Payment::where('status', 'completed')->count(),
                    'failed' => Payment::where('status', 'failed')->count(),
                    'today_amount' => Payment::where('status', 'completed')
                        ->whereDate('payment_date', today())
                        ->sum('amount'),
                ],
                'documents' => [
                    'total' => Document::count(),
                    'pending' => Document::where('status', 'pending')->count(),
                    'approved' => Document::where('status', 'approved')->count(),
                    'rejected' => Document::where('status', 'rejected')->count(),
                    'expired' => Document::where('status', 'expired')->count(),
                    'expiring_soon' => Document::where('expiry_date', '<=', now()->addDays(30))
                        ->where('status', 'approved')
                        ->count(),
                ],
                'customers' => [
                    'total' => Customer::count(),
                    'active' => Customer::where('is_active', true)->count(),
                    'verified' => Customer::where('is_verified', true)->count(),
                    'today' => Customer::whereDate('created_at', today())->count(),
                ],
                'revenue' => [
                    'total' => Payment::where('status', 'completed')->sum('amount'),
                    'today' => Payment::where('status', 'completed')
                        ->whereDate('payment_date', today())
                        ->sum('amount'),
                    'this_month' => Payment::where('status', 'completed')
                        ->whereMonth('payment_date', now()->month)
                        ->whereYear('payment_date', now()->year)
                        ->sum('amount'),
                    'this_year' => Payment::where('status', 'completed')
                        ->whereYear('payment_date', now()->year)
                        ->sum('amount'),
                ],
                'last_updated' => now()->toISOString(),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to calculate dashboard stats', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'error' => 'Failed to calculate statistics',
                'last_updated' => now()->toISOString(),
            ];
        }
    }

    /**
     * Get cached dashboard stats or calculate fresh ones
     */
    public function getDashboardStats(): array
    {
        return Cache::remember('dashboard.stats', 300, function () {
            return $this->calculateDashboardStats();
        });
    }

    /**
     * Clear dashboard stats cache
     */
    public function clearDashboardStatsCache(): void
    {
        Cache::forget('dashboard.stats');
    }

    /**
     * Broadcast custom notification to specific channels
     */
    public function broadcastCustomNotification(array $channels, string $event, array $data): void
    {
        try {
            // This would be implemented based on specific notification requirements
            Log::info('Custom notification broadcasted', [
                'channels' => $channels,
                'event' => $event,
                'data_keys' => array_keys($data),
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to broadcast custom notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}