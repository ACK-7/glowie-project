<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ActivityLog;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Exception;

/**
 * Customer Service
 * 
 * Handles all business logic related to customer management including
 * account management, status transitions, profile updates, and
 * customer relationship workflows.
 */
class CustomerService
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private NotificationService $notificationService
    ) {}

    /**
     * Create a new customer with validation and workflow
     */
    public function createCustomer(array $data): Customer
    {
        DB::beginTransaction();
        
        try {
            // Validate customer data
            $this->validateCustomerData($data);
            
            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            
            // Set default values
            $data['is_active'] = $data['is_active'] ?? true;
            $data['is_verified'] = $data['is_verified'] ?? false;
            $data['total_bookings'] = 0;
            $data['total_spent'] = 0.00;
            
            // Create the customer
            $customer = $this->customerRepository->create($data);
            
            // Generate welcome notifications
            $this->notificationService->sendCustomerWelcomeNotification($customer);
            
            // Create audit trail
            $this->createAuditTrail('customer_created', $customer, [
                'action' => 'Customer created',
                'user_id' => auth()->id(),
                'customer_data' => Arr::except($data, ['password'])
            ]);
            
            DB::commit();
            
            Log::info('Customer created successfully', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'name' => $customer->full_name
            ]);
            
            return $customer;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create customer', [
                'error' => $e->getMessage(),
                'data' => Arr::except($data, ['password'])
            ]);
            throw $e;
        }
    }

    /**
     * Update customer with validation
     */
    public function updateCustomer(int $customerId, array $data): Customer
    {
        DB::beginTransaction();
        
        try {
            $customer = $this->customerRepository->findOrFail($customerId);
            $originalData = $customer->toArray();
            
            // Validate update data
            $this->validateCustomerUpdateData($customer, $data);
            
            // Hash password if provided
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']); // Don't update password if empty
            }
            
            // Update the customer
            $this->customerRepository->update($customerId, $data);
            
            // Refresh the customer model to get updated data
            $customer->refresh();
            
            // Generate notifications if significant changes
            if ($this->hasSignificantChanges($originalData, $data)) {
                $this->notificationService->sendCustomerProfileUpdatedNotification($customer, $originalData, $data);
            }
            
            // Create audit trail
            $this->createAuditTrail('customer_updated', $customer, [
                'changes' => array_diff_assoc(Arr::except($data, ['password']), $originalData),
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            
            Log::info('Customer updated successfully', [
                'customer_id' => $customer->id,
                'changes' => array_keys($data)
            ]);
            
            return $customer;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update customer', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update customer account status with workflow
     */
    public function updateCustomerStatus(int $customerId, bool $isActive, string $reason = null): Customer
    {
        DB::beginTransaction();
        
        try {
            $customer = $this->customerRepository->findOrFail($customerId);
            $oldStatus = $customer->status;
            
            // Update both is_active and status for consistency
            $customer->is_active = $isActive;
            $customer->status = $isActive ? 'active' : 'inactive';
            $customer->save();
            
            // Handle status-specific workflows
            if ($isActive && $oldStatus !== 'active') {
                $this->handleCustomerActivated($customer, $reason);
            } elseif (!$isActive && $oldStatus === 'active') {
                $this->handleCustomerDeactivated($customer, $reason);
            }
            
            // Create audit trail
            $this->createAuditTrail('customer_status_updated', $customer, [
                'old_status' => $oldStatus,
                'new_status' => $customer->status,
                'reason' => $reason,
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            
            Log::info('Customer status updated', [
                'customer_id' => $customer->id,
                'old_status' => $oldStatus,
                'new_status' => $customer->status,
                'reason' => $reason
            ]);
            
            return $customer;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update customer status', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update customer status by string value (active, inactive, suspended)
     */
    public function updateCustomerStatusByString(int $customerId, string $status, string $reason = null): Customer
    {
        DB::beginTransaction();
        
        try {
            $customer = $this->customerRepository->findOrFail($customerId);
            $oldStatus = $customer->status;
            
            // Update status and is_active
            $customer->status = $status;
            $customer->is_active = ($status === 'active');
            $customer->save();
            
            // Handle status-specific workflows
            if ($status === 'active' && $oldStatus !== 'active') {
                $this->handleCustomerActivated($customer, $reason);
            } elseif ($status !== 'active' && $oldStatus === 'active') {
                $this->handleCustomerDeactivated($customer, $reason);
            }
            
            // Create audit trail
            $this->createAuditTrail('customer_status_updated', $customer, [
                'old_status' => $oldStatus,
                'new_status' => $status,
                'reason' => $reason,
                'user_id' => auth()->id()
            ]);
            
            DB::commit();
            
            Log::info('Customer status updated', [
                'customer_id' => $customer->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'reason' => $reason
            ]);
            
            return $customer;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update customer status', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verify customer account
     */
    public function verifyCustomer(int $customerId, string $notes = null): Customer
    {
        DB::beginTransaction();
        
        try {
            $customer = $this->customerRepository->findOrFail($customerId);
            
            if ($customer->is_verified) {
                // If already verified, just return the customer without error
                DB::commit();
                Log::info('Customer already verified', [
                    'customer_id' => $customer->id,
                    'verified_by' => auth()->id()
                ]);
                return $customer;
            }
            
            // Verify the customer
            $customer->verify();
            
            // Generate notifications
            $this->notificationService->sendCustomerVerifiedNotification($customer);
            
            // Create audit trail
            $this->createAuditTrail('customer_verified', $customer, [
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'notes' => $notes
            ]);
            
            DB::commit();
            
            Log::info('Customer verified successfully', [
                'customer_id' => $customer->id,
                'verified_by' => auth()->id()
            ]);
            
            return $customer;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to verify customer', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete customer with reason tracking
     */
    public function deleteCustomer(int $customerId, string $reason): bool
    {
        DB::beginTransaction();
        
        try {
            $customer = $this->customerRepository->findOrFail($customerId);
            
            // Validate deletion is allowed
            if (!$this->canDeleteCustomer($customer)) {
                throw new Exception('Customer cannot be deleted due to active bookings or payments');
            }
            
            // Store customer data for audit
            $customerData = $customer->toArray();
            
            // Create audit trail before deletion
            $this->createAuditTrail('customer_deleted', $customer, [
                'reason' => $reason,
                'user_id' => auth()->id(),
                'customer_data' => Arr::except($customerData, ['password'])
            ]);
            
            // Soft delete the customer
            $deleted = $customer->delete();
            
            DB::commit();
            
            Log::info('Customer deleted successfully', [
                'customer_id' => $customerId,
                'email' => $customerData['email'],
                'reason' => $reason
            ]);
            
            return $deleted;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete customer', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Export customers data
     */
    public function exportCustomers(string $format, array $filters = []): array
    {
        try {
            $customers = $this->customerRepository->getFilteredPaginated($filters, 1000);
            
            $exportData = [
                'format' => $format,
                'total_records' => $customers->total(),
                'generated_at' => now(),
                'file_name' => "customers_export_{$format}_" . now()->format('Y-m-d_H-i-s'),
            ];
            
            // In a real implementation, you would generate the actual file here
            // For now, we'll just return the metadata
            
            Log::info('Customer export completed', [
                'format' => $format,
                'total_records' => $customers->total(),
                'filters' => $filters
            ]);
            
            return $exportData;
            
        } catch (Exception $e) {
            Log::error('Failed to export customers', [
                'format' => $format,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Reset customer password
     */
    public function resetCustomerPassword(int $customerId, bool $sendEmail = true): array
    {
        DB::beginTransaction();
        
        try {
            $customer = $this->customerRepository->findOrFail($customerId);
            
            // Generate a random temporary password
            $temporaryPassword = $this->generateTemporaryPassword();
            
            // Update customer password
            $customer->password = Hash::make($temporaryPassword);
            $customer->password_is_temporary = true;
            $customer->save();
            
            // Send email notification if requested
            if ($sendEmail) {
                $this->notificationService->sendPasswordResetNotification($customer, $temporaryPassword);
            }
            
            // Create audit trail
            $this->createAuditTrail('customer_password_reset', $customer, [
                'reset_by' => auth()->id(),
                'reset_at' => now(),
                'email_sent' => $sendEmail
            ]);
            
            DB::commit();
            
            Log::info('Customer password reset successfully', [
                'customer_id' => $customer->id,
                'reset_by' => auth()->id(),
                'email_sent' => $sendEmail
            ]);
            
            return [
                'success' => true,
                'temporary_password' => $temporaryPassword,
                'email_sent' => $sendEmail,
                'message' => $sendEmail 
                    ? 'Password reset successfully. Temporary password sent to customer email.' 
                    : 'Password reset successfully. Please provide the temporary password to the customer.'
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset customer password', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate a secure temporary password
     */
    private function generateTemporaryPassword(): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        $password = '';
        $length = 12;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $password;
    }

    /**
     * Check if customer can be deleted
     */
    private function canDeleteCustomer(Customer $customer): bool
    {
        // Cannot delete if customer has active bookings
        if ($customer->hasActiveBookings()) {
            return false;
        }
        
        // Cannot delete if customer has pending payments
        if ($customer->getPendingPayments() > 0) {
            return false;
        }
        
        return true;
    }

    /**
     * Search customers by multiple criteria
     */
    public function searchCustomers(string $query, array $filters = []): Collection
    {
        return $this->customerRepository->searchCustomers($query);
    }

    /**
     * Get customer statistics and analytics
     */
    public function getCustomerStatistics(): array
    {
        $stats = $this->customerRepository->getCustomerStatistics();
        
        // Add additional analytics
        $stats['customers_requiring_attention'] = $this->getCustomersRequiringAttention()->count();
        $stats['top_customers'] = $this->customerRepository->getTopCustomersBySpending(5);
        $stats['retention_metrics'] = $this->customerRepository->getRetentionMetrics();
        
        return $stats;
    }

    /**
     * Get customers requiring attention
     */
    public function getCustomersRequiringAttention(): Collection
    {
        return $this->customerRepository->getRequiringAttention();
    }

    /**
     * Get customer profile with complete information
     */
    public function getCustomerProfile(int $customerId): array
    {
        $customer = $this->customerRepository->findWithRelations($customerId, [
            'bookings', 'quotes', 'payments', 'documents'
        ]);
        
        if (!$customer) {
            throw new Exception('Customer not found');
        }
        
        return [
            'customer' => $customer,
            'statistics' => [
                'total_bookings' => $customer->bookings->count(),
                'total_spent' => $customer->total_spent,
                'average_booking_value' => $customer->getAverageBookingValue(),
                'customer_tier' => $customer->getCustomerTier(),
                'discount_percentage' => $customer->getDiscountPercentage(),
                'last_booking_date' => $customer->getLastBookingDate(),
                'pending_payments' => $customer->getPendingPayments(),
                'has_active_bookings' => $customer->hasActiveBookings(),
            ],
            'recent_activity' => $this->getCustomerRecentActivity($customerId),
        ];
    }

    /**
     * Get customer recent activity
     */
    public function getCustomerRecentActivity(int $customerId, int $limit = 10): Collection
    {
        $customer = $this->customerRepository->findOrFail($customerId);
        
        // Get recent bookings, quotes, and payments
        $recentBookings = $customer->bookings()->latest()->limit($limit)->get();
        $recentQuotes = $customer->quotes()->latest()->limit($limit)->get();
        $recentPayments = $customer->payments()->latest()->limit($limit)->get();
        
        // Combine and sort by date
        $activities = collect();
        
        foreach ($recentBookings as $booking) {
            $activities->push([
                'type' => 'booking',
                'id' => $booking->id,
                'reference' => $booking->booking_reference,
                'status' => $booking->status,
                'amount' => $booking->total_amount,
                'date' => $booking->created_at,
                'description' => "Booking {$booking->booking_reference} - {$booking->status_label}"
            ]);
        }
        
        foreach ($recentQuotes as $quote) {
            $activities->push([
                'type' => 'quote',
                'id' => $quote->id,
                'reference' => $quote->quote_reference,
                'status' => $quote->status,
                'amount' => $quote->total_amount,
                'date' => $quote->created_at,
                'description' => "Quote {$quote->quote_reference} - {$quote->status_label}"
            ]);
        }
        
        foreach ($recentPayments as $payment) {
            $activities->push([
                'type' => 'payment',
                'id' => $payment->id,
                'reference' => $payment->payment_reference,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'date' => $payment->created_at,
                'description' => "Payment {$payment->payment_reference} - {$payment->status}"
            ]);
        }
        
        return $activities->sortByDesc('date')->take($limit);
    }

    /**
     * Handle customer activation workflow
     */
    private function handleCustomerActivated(Customer $customer, string $reason = null): void
    {
        // Send activation notification
        $this->notificationService->sendCustomerActivatedNotification($customer, $reason);
        
        // Log activation
        Log::info('Customer activated', [
            'customer_id' => $customer->id,
            'reason' => $reason
        ]);
    }

    /**
     * Handle customer deactivation workflow
     */
    private function handleCustomerDeactivated(Customer $customer, string $reason = null): void
    {
        // Send deactivation notification
        $this->notificationService->sendCustomerDeactivatedNotification($customer, $reason);
        
        // Cancel any pending quotes
        $pendingQuotes = $customer->quotes()->where('status', 'pending')->get();
        foreach ($pendingQuotes as $quote) {
            $quote->status = 'cancelled';
            $quote->notes = 'Cancelled due to customer deactivation: ' . $reason;
            $quote->save();
        }
        
        // Log deactivation
        Log::info('Customer deactivated', [
            'customer_id' => $customer->id,
            'reason' => $reason,
            'cancelled_quotes' => $pendingQuotes->count()
        ]);
    }

    /**
     * Validate customer data for creation
     */
    private function validateCustomerData(array $data): void
    {
        // Check for duplicate email
        if (isset($data['email'])) {
            $existingCustomer = $this->customerRepository->findByEmail($data['email']);
            if ($existingCustomer) {
                throw new Exception('Email address already exists');
            }
        }
        
        // Validate required fields
        $requiredFields = ['first_name', 'last_name', 'email', 'phone', 'country'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Validate date of birth if provided
        if (isset($data['date_of_birth']) && strtotime($data['date_of_birth']) >= time()) {
            throw new Exception('Date of birth must be in the past');
        }
    }

    /**
     * Validate customer data for updates
     */
    private function validateCustomerUpdateData(Customer $customer, array $data): void
    {
        // Check for duplicate email (excluding current customer)
        if (isset($data['email']) && $data['email'] !== $customer->email) {
            $existingCustomer = $this->customerRepository->findByEmail($data['email']);
            if ($existingCustomer && $existingCustomer->id !== $customer->id) {
                throw new Exception('Email address already exists');
            }
        }
        
        // Validate email format if provided
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Validate date of birth if provided
        if (isset($data['date_of_birth']) && strtotime($data['date_of_birth']) >= time()) {
            throw new Exception('Date of birth must be in the past');
        }
    }

    /**
     * Check if changes are significant enough to notify
     */
    private function hasSignificantChanges(array $original, array $changes): bool
    {
        $significantFields = [
            'email', 'phone', 'address', 'country', 'city'
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
    private function createAuditTrail(string $action, Customer $customer, array $details): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => Customer::class,
            'model_id' => $customer->id,
            'changes' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}