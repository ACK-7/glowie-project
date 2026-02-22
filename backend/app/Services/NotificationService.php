<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Quote;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

/**
 * Notification Service
 * 
 * Handles all automated notification generation for the system including
 * email notifications, in-app notifications, and SMS alerts.
 */
class NotificationService
{
    /**
     * Send booking created notification
     */
    public function sendBookingCreatedNotification(Booking $booking): void
    {
        try {
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $booking->customer_id,
                'type' => 'booking_created',
                'title' => 'Booking Created Successfully',
                'message' => "Your booking {$booking->booking_reference} has been created and is pending confirmation.",
                'data' => [
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'total_amount' => $booking->total_amount,
                    'currency' => $booking->currency,
                ],
                'channels' => ['database', 'email']
            ]);
            
            Log::info('Booking created notification sent', [
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send booking created notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send booking status update notification
     */
    public function sendBookingStatusUpdateNotification(Booking $booking, string $oldStatus, string $newStatus): void
    {
        try {
            $statusMessages = [
                'confirmed' => 'Your booking has been confirmed and is being processed.',
                'in_transit' => 'Your vehicle is now in transit. You can track its progress.',
                'delivered' => 'Your vehicle has been delivered successfully!',
                'cancelled' => 'Your booking has been cancelled.',
            ];
            
            $message = $statusMessages[$newStatus] ?? "Your booking status has been updated to {$newStatus}.";
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $booking->customer_id,
                'type' => 'booking_status_updated',
                'title' => 'Booking Status Updated',
                'message' => $message,
                'data' => [
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ],
                'channels' => ['database', 'email', 'sms']
            ]);
            
            Log::info('Booking status update notification sent', [
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send booking status update notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send quote created notification
     */
    public function sendQuoteCreatedNotification(Quote $quote): void
    {
        try {
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $quote->customer_id,
                'type' => 'quote_created',
                'title' => 'Quote Generated',
                'message' => "Your quote {$quote->quote_reference} has been generated and is pending approval.",
                'data' => [
                    'quote_id' => $quote->id,
                    'quote_reference' => $quote->quote_reference,
                    'total_amount' => $quote->total_amount,
                    'currency' => $quote->currency,
                    'valid_until' => $quote->valid_until,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send quote created notification', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send quote approved notification with login credentials
     */
    public function sendQuoteApprovedNotification(Quote $quote, string $temporaryPassword = null): void
    {
        try {
            $customer = Customer::find($quote->customer_id);
            
            // Base message
            $message = "Your quote {$quote->quote_reference} has been approved! You can now proceed with booking.";
            
            // Add login credentials if provided
            if ($temporaryPassword && $customer) {
                $message .= "\n\nYour Customer Portal Login Details:\nEmail: {$customer->email}\nTemporary Password: {$temporaryPassword}\n\nPlease log in to your customer portal to confirm your booking and track your shipment.";
            }
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $quote->customer_id,
                'type' => 'quote_approved',
                'title' => 'Quote Approved - Login Credentials Included',
                'message' => $message,
                'data' => [
                    'quote_id' => $quote->id,
                    'quote_reference' => $quote->quote_reference,
                    'total_amount' => $quote->total_amount,
                    'valid_until' => $quote->valid_until,
                    'customer_email' => $customer->email ?? null,
                    'has_login_credentials' => !empty($temporaryPassword),
                    'portal_url' => config('app.frontend_url') . '/customer-portal',
                ],
                'channels' => ['database', 'email', 'sms']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send quote approved notification', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send quote converted notification
     */
    public function sendQuoteConvertedNotification(Quote $quote, Booking $booking): void
    {
        try {
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $quote->customer_id,
                'type' => 'quote_converted',
                'title' => 'Quote Converted to Booking',
                'message' => "Your quote {$quote->quote_reference} has been converted to booking {$booking->booking_reference}.",
                'data' => [
                    'quote_id' => $quote->id,
                    'quote_reference' => $quote->quote_reference,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send quote converted notification', [
                'quote_id' => $quote->id,
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send shipment created notification
     */
    public function sendShipmentCreatedNotification(Shipment $shipment): void
    {
        try {
            $booking = $shipment->booking;
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $booking->customer_id,
                'type' => 'shipment_created',
                'title' => 'Shipment Created',
                'message' => "Your shipment has been created with tracking number {$shipment->tracking_number}.",
                'data' => [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send shipment created notification', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send shipment status update notification
     */
    public function sendShipmentStatusUpdateNotification(Shipment $shipment, string $oldStatus, string $newStatus, string $location = null): void
    {
        try {
            $booking = $shipment->booking;
            $locationText = $location ? " at {$location}" : '';
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $booking->customer_id,
                'type' => 'shipment_status_updated',
                'title' => 'Shipment Status Updated',
                'message' => "Your shipment {$shipment->tracking_number} is now {$newStatus}{$locationText}.",
                'data' => [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'location' => $location,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send shipment status update notification', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send missing documents notification
     */
    public function sendMissingDocumentsNotification(Booking $booking, array $missingDocuments): void
    {
        try {
            $documentList = implode(', ', $missingDocuments);
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $booking->customer_id,
                'type' => 'missing_documents',
                'title' => 'Missing Documents Required',
                'message' => "Please upload the following documents for booking {$booking->booking_reference}: {$documentList}",
                'data' => [
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'missing_documents' => $missingDocuments,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send missing documents notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment completed notification
     */
    public function sendPaymentCompletedNotification(Booking $booking): void
    {
        try {
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $booking->customer_id,
                'type' => 'payment_completed',
                'title' => 'Payment Completed',
                'message' => "Payment for booking {$booking->booking_reference} has been completed successfully.",
                'data' => [
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'total_amount' => $booking->total_amount,
                    'paid_amount' => $booking->paid_amount,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send payment completed notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send customer welcome notification with login credentials
     */
    public function sendCustomerWelcomeNotification(Customer $customer, string $temporaryPassword = null): void
    {
        try {
            $message = "Welcome {$customer->first_name}! Your account has been created successfully.";
            
            if ($temporaryPassword) {
                $message .= "\n\nYour Login Details:\nEmail: {$customer->email}\nTemporary Password: {$temporaryPassword}\n\nPlease log in to your customer portal to manage your quotes and bookings.";
            }
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $customer->id,
                'type' => 'customer_welcome',
                'title' => 'Welcome to ShipWithGlowie Auto',
                'message' => $message,
                'data' => [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->full_name,
                    'customer_email' => $customer->email,
                    'has_login_credentials' => !empty($temporaryPassword),
                    'portal_url' => config('app.frontend_url') . '/customer-portal',
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send customer welcome notification', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send delivery confirmation notification
     */
    public function sendDeliveryConfirmationNotification(Shipment $shipment): void
    {
        try {
            $booking = $shipment->booking;
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $booking->customer_id,
                'type' => 'delivery_confirmation',
                'title' => 'Vehicle Delivered Successfully',
                'message' => "Your vehicle has been delivered successfully! Tracking number: {$shipment->tracking_number}",
                'data' => [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'delivery_date' => $shipment->actual_arrival,
                ],
                'channels' => ['database', 'email', 'sms']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send delivery confirmation notification', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send shipment delayed notification
     */
    public function sendShipmentDelayedNotification(Shipment $shipment, string $reason = null): void
    {
        try {
            $booking = $shipment->booking;
            $reasonText = $reason ? " Reason: {$reason}" : '';
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $booking->customer_id,
                'type' => 'shipment_delayed',
                'title' => 'Shipment Delayed',
                'message' => "Your shipment {$shipment->tracking_number} has been delayed.{$reasonText}",
                'data' => [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'days_delayed' => $shipment->days_delayed,
                    'reason' => $reason,
                ],
                'channels' => ['database', 'email', 'sms']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send shipment delayed notification', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create notification record and send via specified channels
     */
    private function createNotification(array $data): void
    {
        // Create database notification
        $notification = Notification::create([
            'notifiable_type' => $data['notifiable_type'],
            'notifiable_id' => $data['notifiable_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'data' => $data['data'] ?? [],
            'channels' => $data['channels'] ?? ['database'],
            'is_read' => false,
        ]);

        // Send via specified channels
        foreach ($data['channels'] as $channel) {
            $this->sendViaChannel($channel, $notification);
        }
    }

    /**
     * Send notification via specific channel
     */
    private function sendViaChannel(string $channel, Notification $notification): void
    {
        try {
            switch ($channel) {
                case 'email':
                    $this->sendEmailNotification($notification);
                    break;
                    
                case 'sms':
                    $this->sendSmsNotification($notification);
                    break;
                    
                case 'database':
                    // Already stored in database
                    break;
                    
                default:
                    Log::warning('Unknown notification channel', ['channel' => $channel]);
            }
        } catch (Exception $e) {
            Log::error('Failed to send notification via channel', [
                'channel' => $channel,
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(Notification $notification): void
    {
        try {
            // Get the customer
            $customer = null;
            if ($notification->notifiable_type === Customer::class) {
                $customer = Customer::find($notification->notifiable_id);
            }
            
            if (!$customer || !$customer->email) {
                Log::warning('Cannot send email notification - customer not found or no email', [
                    'notification_id' => $notification->id,
                    'notifiable_id' => $notification->notifiable_id
                ]);
                return;
            }

            // Send actual email for quote approval
            if ($notification->type === 'quote_approved' && isset($notification->data['quote_id'])) {
                $quote = Quote::with(['customer', 'route'])->find($notification->data['quote_id']);
                if ($quote) {
                    $password = $this->extractPasswordFromMessage($notification->message);
                    
                    // Send the email
                    \Illuminate\Support\Facades\Mail::to($customer->email)
                        ->send(new \App\Mail\QuoteApprovedMail($quote, $password));
                    
                    Log::info('✅ Quote Approved Email Sent', [
                        'to' => $customer->email,
                        'quote_reference' => $quote->quote_reference,
                        'has_credentials' => !empty($password)
                    ]);
                    
                    return;
                }
            }

            // FOR DEVELOPMENT: Log other email types
            Log::info('📧 EMAIL NOTIFICATION (Development Mode)', [
                'to' => $customer->email,
                'customer_name' => $customer->first_name ?? 'Customer',
                'subject' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'notification_id' => $notification->id
            ]);

            Log::info('Email notification logged successfully (Development Mode)', [
                'notification_id' => $notification->id,
                'type' => $notification->type,
                'recipient' => $customer->email
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send email notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Extract password from notification message
     */
    private function extractPasswordFromMessage(string $message): ?string
    {
        // Extract password from message using regex
        if (preg_match('/Temporary Password: ([^\s\n]+)/', $message, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Send SMS notification
     */
    private function sendSmsNotification(Notification $notification): void
    {
        // Implementation would depend on your SMS service
        // For now, just log that SMS would be sent
        Log::info('SMS notification would be sent', [
            'notification_id' => $notification->id,
            'type' => $notification->type,
            'recipient' => $notification->notifiable_id
        ]);
    }

    // Placeholder methods for other notification types
    public function sendBookingUpdatedNotification(Booking $booking, array $originalData, array $changes): void {}
    public function sendBookingDeletedNotification(Booking $booking, string $reason): void {}
    public function sendBookingCompletedNotification(Booking $booking): void {}
    public function sendQuoteUpdatedNotification(Quote $quote, array $originalData, array $changes): void {}
    public function sendQuoteRejectedNotification(Quote $quote, string $reason): void {}
    public function sendQuoteExpiredNotification(Quote $quote): void {}
    public function sendQuoteValidityExtendedNotification(Quote $quote, $oldValidUntil, int $days, string $reason = null): void {}
    public function sendQuoteFeeAddedNotification(Quote $quote, string $name, float $amount): void {}
    public function sendCustomerProfileUpdatedNotification(Customer $customer, array $originalData, array $changes): void {}
    public function sendCustomerVerifiedNotification(Customer $customer): void {}
    public function sendCustomerActivatedNotification(Customer $customer, string $reason = null): void {}
    public function sendCustomerDeactivatedNotification(Customer $customer, string $reason = null): void {}
    public function sendShipmentUpdatedNotification(Shipment $shipment, array $originalData, array $changes): void {}
    public function sendEstimatedArrivalUpdatedNotification(Shipment $shipment, $oldDate, $newDate, string $reason = null): void {}
    public function sendDelayDetectedNotification(Shipment $shipment, int $delayDays, string $reason = null): void {}
    public function sendDelayEscalationNotification(Shipment $shipment): void {}
    public function sendTrackingInformationNotification(Booking $booking): void {}
    public function sendCustomsDocumentsRequiredNotification(Booking $booking): void {}
    public function sendRefundProcessingNotification(Booking $booking, string $reason = null): void {}

    /**
     * Send password reset notification with temporary password
     */
    public function sendPasswordResetNotification(Customer $customer, string $temporaryPassword): void
    {
        try {
            $loginUrl = config('app.frontend_url') . '/customer/login';
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $customer->id,
                'type' => 'password_reset',
                'title' => 'Password Reset - Temporary Password',
                'message' => "Your password has been reset by an administrator. Your temporary password is: {$temporaryPassword}\n\nPlease log in and change your password immediately for security reasons.\n\nLogin at: {$loginUrl}",
                'data' => [
                    'customer_id' => $customer->id,
                    'customer_email' => $customer->email,
                    'temporary_password' => $temporaryPassword,
                    'login_url' => $loginUrl,
                    'reset_at' => now()->toISOString(),
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send password reset notification', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send document uploaded notification
     */
    public function sendDocumentUploadedNotification(\App\Models\Document $document): void
    {
        try {
            $booking = $document->booking;
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $document->customer_id,
                'type' => 'document_uploaded',
                'title' => 'Document Uploaded Successfully',
                'message' => "Your {$document->type_label} has been uploaded for booking {$booking->booking_reference} and is pending verification.",
                'data' => [
                    'document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send document uploaded notification', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send document approved notification
     */
    public function sendDocumentApprovedNotification(\App\Models\Document $document, string $notes = null): void
    {
        try {
            $booking = $document->booking;
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $document->customer_id,
                'type' => 'document_approved',
                'title' => 'Document Approved',
                'message' => "Your {$document->type_label} for booking {$booking->booking_reference} has been approved.",
                'data' => [
                    'document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'notes' => $notes,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send document approved notification', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send document rejected notification
     */
    public function sendDocumentRejectedNotification(\App\Models\Document $document, string $reason): void
    {
        try {
            $booking = $document->booking;
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $document->customer_id,
                'type' => 'document_rejected',
                'title' => 'Document Rejected',
                'message' => "Your {$document->type_label} for booking {$booking->booking_reference} has been rejected. Reason: {$reason}",
                'data' => [
                    'document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'reason' => $reason,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send document rejected notification', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send document expired notification
     */
    public function sendDocumentExpiredNotification(\App\Models\Document $document): void
    {
        try {
            $booking = $document->booking;
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $document->customer_id,
                'type' => 'document_expired',
                'title' => 'Document Expired',
                'message' => "Your {$document->type_label} for booking {$booking->booking_reference} has expired. Please upload a new document.",
                'data' => [
                    'document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'expiry_date' => $document->expiry_date,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send document expired notification', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send document expiry updated notification
     */
    public function sendDocumentExpiryUpdatedNotification(\App\Models\Document $document, $oldExpiry, $newExpiry): void
    {
        try {
            $booking = $document->booking;
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $document->customer_id,
                'type' => 'document_expiry_updated',
                'title' => 'Document Expiry Updated',
                'message' => "The expiry date for your {$document->type_label} for booking {$booking->booking_reference} has been updated.",
                'data' => [
                    'document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'old_expiry' => $oldExpiry,
                    'new_expiry' => $newExpiry,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send document expiry updated notification', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send document deleted notification
     */
    public function sendDocumentDeletedNotification(\App\Models\Document $document, string $reason): void
    {
        try {
            $booking = $document->booking;
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $document->customer_id,
                'type' => 'document_deleted',
                'title' => 'Document Deleted',
                'message' => "Your {$document->type_label} for booking {$booking->booking_reference} has been deleted. Reason: {$reason}",
                'data' => [
                    'document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'reason' => $reason,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send document deleted notification', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send documents complete notification
     */
    public function sendDocumentsCompleteNotification(\App\Models\Booking $booking): void
    {
        try {
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $booking->customer_id,
                'type' => 'documents_complete',
                'title' => 'All Documents Complete',
                'message' => "All required documents for booking {$booking->booking_reference} have been uploaded and verified.",
                'data' => [
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send documents complete notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment created notification
     */
    public function sendPaymentCreatedNotification(\App\Models\Payment $payment): void
    {
        try {
            $booking = $payment->booking;
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $payment->customer_id,
                'type' => 'payment_created',
                'title' => 'Payment Record Created',
                'message' => "A payment record has been created for booking {$booking->booking_reference}. Amount: {$payment->formatted_amount}",
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payment_method' => $payment->payment_method,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send payment created notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment failed notification
     */
    public function sendPaymentFailedNotification(\App\Models\Payment $payment, string $reason = null): void
    {
        try {
            $booking = $payment->booking;
            $reasonText = $reason ? " Reason: {$reason}" : '';
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $payment->customer_id,
                'type' => 'payment_failed',
                'title' => 'Payment Failed',
                'message' => "Payment {$payment->payment_reference} for booking {$booking->booking_reference} has failed.{$reasonText}",
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'amount' => $payment->amount,
                    'reason' => $reason,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send payment failed notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send refund processed notification
     */
    public function sendRefundProcessedNotification(\App\Models\Payment $originalPayment, \App\Models\Payment $refundPayment, string $reason = null): void
    {
        try {
            $booking = $originalPayment->booking;
            $reasonText = $reason ? " Reason: {$reason}" : '';
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $originalPayment->customer_id,
                'type' => 'refund_processed',
                'title' => 'Refund Processed',
                'message' => "A refund of {$refundPayment->formatted_amount} has been processed for payment {$originalPayment->payment_reference}.{$reasonText}",
                'data' => [
                    'original_payment_id' => $originalPayment->id,
                    'refund_payment_id' => $refundPayment->id,
                    'original_payment_reference' => $originalPayment->payment_reference,
                    'refund_payment_reference' => $refundPayment->payment_reference,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'refund_amount' => abs($refundPayment->amount),
                    'reason' => $reason,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send refund processed notification', [
                'original_payment_id' => $originalPayment->id,
                'refund_payment_id' => $refundPayment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment cancelled notification
     */
    public function sendPaymentCancelledNotification(\App\Models\Payment $payment, string $reason = null): void
    {
        try {
            $booking = $payment->booking;
            $reasonText = $reason ? " Reason: {$reason}" : '';
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $payment->customer_id,
                'type' => 'payment_cancelled',
                'title' => 'Payment Cancelled',
                'message' => "Payment {$payment->payment_reference} for booking {$booking->booking_reference} has been cancelled.{$reasonText}",
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'amount' => $payment->amount,
                    'reason' => $reason,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send payment cancelled notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment retry notification
     */
    public function sendPaymentRetryNotification(\App\Models\Payment $payment): void
    {
        try {
            $booking = $payment->booking;
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $payment->customer_id,
                'type' => 'payment_retry',
                'title' => 'Payment Retry Initiated',
                'message' => "Payment {$payment->payment_reference} for booking {$booking->booking_reference} is being retried.",
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'amount' => $payment->amount,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send payment retry notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment reminder notification
     */
    public function sendPaymentReminderNotification(\App\Models\Payment $payment, string $urgency = 'gentle'): void
    {
        try {
            $booking = $payment->booking;
            $titles = [
                'gentle' => 'Payment Reminder',
                'urgent' => 'Urgent: Payment Overdue'
            ];
            
            $messages = [
                'gentle' => "Friendly reminder: Payment {$payment->payment_reference} for booking {$booking->booking_reference} is pending.",
                'urgent' => "URGENT: Payment {$payment->payment_reference} for booking {$booking->booking_reference} is overdue. Please make payment immediately."
            ];
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $payment->customer_id,
                'type' => 'payment_reminder',
                'title' => $titles[$urgency] ?? $titles['gentle'],
                'message' => $messages[$urgency] ?? $messages['gentle'],
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'amount' => $payment->amount,
                    'days_overdue' => $payment->days_overdue,
                    'urgency' => $urgency,
                ],
                'channels' => $urgency === 'urgent' ? ['database', 'email', 'sms'] : ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send payment reminder notification', [
                'payment_id' => $payment->id,
                'urgency' => $urgency,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment escalation notification
     */
    public function sendPaymentEscalationNotification(\App\Models\Payment $payment): void
    {
        try {
            $booking = $payment->booking;
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $payment->customer_id,
                'type' => 'payment_escalation',
                'title' => 'Payment Escalation Notice',
                'message' => "FINAL NOTICE: Payment {$payment->payment_reference} for booking {$booking->booking_reference} is severely overdue. Immediate action required.",
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'amount' => $payment->amount,
                    'days_overdue' => $payment->days_overdue,
                ],
                'channels' => ['database', 'email', 'sms']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send payment escalation notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send password reset instructions notification
     */
    public function sendPasswordResetInstructions(Customer $customer, string $resetToken): void
    {
        try {
            $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $resetToken;
            
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $customer->id,
                'type' => 'password_reset',
                'title' => 'Password Reset Instructions',
                'message' => "You requested a password reset. Click the link below to reset your password:\n\n{$resetUrl}\n\nThis link will expire in 2 hours. If you didn't request this, please ignore this message.",
                'data' => [
                    'customer_id' => $customer->id,
                    'customer_email' => $customer->email,
                    'reset_token' => $resetToken,
                    'reset_url' => $resetUrl,
                    'expires_at' => now()->addHours(2)->toISOString(),
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send password reset instructions', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send booking fully paid notification
     */
    public function sendBookingFullyPaidNotification(\App\Models\Booking $booking): void
    {
        try {
            $this->createNotification([
                'notifiable_type' => Customer::class,
                'notifiable_id' => $booking->customer_id,
                'type' => 'booking_fully_paid',
                'title' => 'Booking Fully Paid',
                'message' => "Congratulations! Booking {$booking->booking_reference} has been fully paid. Total amount: {$booking->total_amount} {$booking->currency}",
                'data' => [
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'total_amount' => $booking->total_amount,
                    'paid_amount' => $booking->paid_amount,
                    'currency' => $booking->currency,
                ],
                'channels' => ['database', 'email']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send booking fully paid notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}