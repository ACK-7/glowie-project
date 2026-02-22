<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseApiController;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * Customer Controller
 * 
 * Handles customer profile management with complete information display,
 * customer search across multiple fields, and account status management
 * with reason tracking.
 * 
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5
 */
class CustomerController extends BaseApiController
{
    public function __construct(
        private CustomerService $customerService,
        private CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * Display a listing of customers with filtering and search
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $this->validateFilters($request);
            
            // Get filtered customers with pagination
            $customers = $this->customerRepository->getFilteredPaginated($filters, $request->get('per_page', 15));
            
            // Add computed fields to each customer
            $customers->getCollection()->transform(function ($customer) {
                $customerData = $customer->toArray();
                $customerData['full_name'] = $customer->full_name;
                $customerData['customer_tier'] = $customer->getCustomerTier();
                $customerData['discount_percentage'] = $customer->getDiscountPercentage();
                $customerData['has_active_bookings'] = $customer->hasActiveBookings();
                $customerData['pending_payments'] = $customer->getPendingPayments();
                $customerData['average_booking_value'] = $customer->getAverageBookingValue();
                $customerData['last_booking_date'] = $customer->getLastBookingDate();
                return $customerData;
            });
            
            // Log activity
            $this->logActivity('customers_listed', null, null, ['filters' => $filters]);
            
            return $this->paginatedResponse($customers, 'Customers retrieved successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created customer
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, Customer::validationRules());
            
            $customer = $this->customerService->createCustomer($validatedData);
            
            return $this->createdResponse($customer, 'Customer created successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified customer with complete information
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $customer = $this->customerRepository->findWithRelations($id, [
                'bookings.vehicle', 'bookings.route', 'bookings.shipment',
                'quotes.route', 'documents', 'payments', 'chatMessages'
            ]);
            
            if (!$customer) {
                return $this->notFoundResponse('Customer');
            }
            
            // Add computed fields and complete information
            $customerData = $customer->toArray();
            $customerData['full_name'] = $customer->full_name;
            $customerData['customer_tier'] = $customer->getCustomerTier();
            $customerData['discount_percentage'] = $customer->getDiscountPercentage();
            $customerData['has_active_bookings'] = $customer->hasActiveBookings();
            $customerData['pending_payments'] = $customer->getPendingPayments();
            $customerData['average_booking_value'] = $customer->getAverageBookingValue();
            $customerData['last_booking_date'] = $customer->getLastBookingDate();
            
            // Add booking statistics
            $customerData['booking_statistics'] = [
                'total_bookings' => $customer->bookings->count(),
                'completed_bookings' => $customer->bookings->where('status', 'delivered')->count(),
                'active_bookings' => $customer->bookings->whereNotIn('status', ['delivered', 'cancelled'])->count(),
                'cancelled_bookings' => $customer->bookings->where('status', 'cancelled')->count(),
            ];
            
            // Add quote statistics
            $customerData['quote_statistics'] = [
                'total_quotes' => $customer->quotes->count(),
                'pending_quotes' => $customer->quotes->where('status', 'pending')->count(),
                'approved_quotes' => $customer->quotes->where('status', 'approved')->count(),
                'converted_quotes' => $customer->quotes->where('status', 'converted')->count(),
            ];
            
            // Add payment statistics
            $customerData['payment_statistics'] = [
                'total_payments' => $customer->payments->count(),
                'completed_payments' => $customer->payments->where('status', 'completed')->count(),
                'pending_payments_count' => $customer->payments->where('status', 'pending')->count(),
                'total_paid' => $customer->payments->where('status', 'completed')->sum('amount'),
            ];
            
            $this->logActivity('customer_viewed', Customer::class, $id);
            
            return $this->successResponse($customerData, 'Customer retrieved successfully');
            
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified customer
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $customer = $this->customerRepository->find($id);
            if (!$customer) {
                return $this->notFoundResponse('Customer');
            }
            
            $validatedData = $this->validateRequest($request, Customer::updateValidationRules($id));
            
            $updatedCustomer = $this->customerService->updateCustomer($id, $validatedData);
            
            return $this->updatedResponse($updatedCustomer, 'Customer updated successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified customer (soft delete)
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            // Make reason and confirmation optional for easier frontend integration
            $validatedData = $this->validateRequest($request, [
                'reason' => 'nullable|string|max:500',
                'confirmation' => 'nullable|boolean'
            ]);
            
            $reason = $validatedData['reason'] ?? 'Deleted by admin';
            $deleted = $this->customerService->deleteCustomer($id, $reason);
            
            if ($deleted) {
                return $this->deletedResponse('Customer deleted successfully');
            }
            
            return $this->errorResponse('Failed to delete customer');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Search customers across multiple fields
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'query' => 'required|string|min:2|max:100',
                'fields' => 'nullable|array',
                'fields.*' => 'in:name,email,phone,id_number'
            ]);
            
            $customers = $this->customerRepository->searchCustomers(
                $validatedData['query'],
                $validatedData['fields'] ?? ['name', 'email', 'phone']
            );
            
            // Add computed fields
            $customers->transform(function ($customer) {
                $customerData = $customer->toArray();
                $customerData['full_name'] = $customer->full_name;
                $customerData['customer_tier'] = $customer->getCustomerTier();
                $customerData['has_active_bookings'] = $customer->hasActiveBookings();
                return $customerData;
            });
            
            return $this->collectionResponse($customers, 'Search results retrieved successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update customer account status with reason tracking
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            // Accept status string: active, inactive, or suspended
            $validatedData = $this->validateRequest($request, [
                'status' => 'required|string|in:active,inactive,suspended',
                'reason' => 'nullable|string|max:500'
            ]);
            
            $reason = $validatedData['reason'] ?? "Status changed to {$validatedData['status']} by admin";
            
            $customer = $this->customerService->updateCustomerStatusByString(
                $id,
                $validatedData['status'],
                $reason
            );
            
            return $this->updatedResponse($customer, 'Customer status updated successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Verify customer account
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'verification_notes' => 'nullable|string|max:500'
            ]);
            
            $customer = $this->customerService->verifyCustomer($id, $validatedData['verification_notes'] ?? null);
            
            return $this->updatedResponse($customer, 'Customer verified successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get customer booking history
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function bookingHistory(int $id, Request $request): JsonResponse
    {
        try {
            $customer = $this->customerRepository->find($id);
            if (!$customer) {
                return $this->notFoundResponse('Customer');
            }
            
            $filters = $request->validate([
                'status' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);
            
            $bookings = $this->customerRepository->getCustomerBookings($id, $filters, $request->get('per_page', 15));
            
            return $this->paginatedResponse($bookings, 'Customer booking history retrieved successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get customer communication history
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function communicationHistory(int $id, Request $request): JsonResponse
    {
        try {
            $customer = $this->customerRepository->find($id);
            if (!$customer) {
                return $this->notFoundResponse('Customer');
            }
            
            $communications = $this->customerRepository->getCommunicationHistory($id, $request->get('per_page', 20));
            
            return $this->paginatedResponse($communications, 'Customer communication history retrieved successfully');
            
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get customer statistics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $filters = $this->validateFilters($request);
            $statistics = $this->customerRepository->getCustomerStatistics($filters);
            
            return $this->successResponse($statistics, 'Customer statistics retrieved successfully');
            
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get customers by tier
     * 
     * @param string $tier
     * @return JsonResponse
     */
    public function byTier(string $tier): JsonResponse
    {
        try {
            if (!in_array($tier, ['bronze', 'silver', 'gold', 'platinum'])) {
                return $this->errorResponse('Invalid customer tier', 400);
            }
            
            $customers = $this->customerRepository->getCustomersByTier($tier);
            
            return $this->collectionResponse($customers, "Customers in {$tier} tier retrieved successfully");
            
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get customers requiring attention
     * 
     * @return JsonResponse
     */
    public function requiresAttention(): JsonResponse
    {
        try {
            $customers = $this->customerRepository->getCustomersRequiringAttention();
            
            return $this->collectionResponse($customers, 'Customers requiring attention retrieved successfully');
            
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Export customers data
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'format' => 'required|in:csv,excel,pdf',
                'filters' => 'nullable|array'
            ]);
            
            $exportData = $this->customerService->exportCustomers(
                $validatedData['format'],
                $validatedData['filters'] ?? []
            );
            
            return $this->successResponse($exportData, 'Customer export completed successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Reset customer password
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        try {
            $customer = $this->customerRepository->find($id);
            if (!$customer) {
                return $this->notFoundResponse('Customer');
            }
            
            $validatedData = $this->validateRequest($request, [
                'send_email' => 'nullable|boolean'
            ]);
            
            $sendEmail = $validatedData['send_email'] ?? true;
            
            $result = $this->customerService->resetCustomerPassword($id, $sendEmail);
            
            return $this->successResponse($result, 'Customer password reset successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Validate filter parameters (enhanced to match BookingController pattern)
     * 
     * @param Request $request
     * @return array
     */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            // Status and verification filters
            'status' => 'nullable|string|in:active,inactive,suspended',
            'is_active' => 'nullable|boolean',
            'is_verified' => 'nullable|boolean',
            'customer_tier' => 'nullable|in:bronze,silver,gold,platinum',
            
            // Location filters
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            
            // Activity filters
            'has_bookings' => 'nullable|boolean',
            'has_active_bookings' => 'nullable|boolean',
            'newsletter_subscribed' => 'nullable|boolean',
            
            // Date range filters
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'last_login_from' => 'nullable|date',
            'last_login_to' => 'nullable|date|after_or_equal:last_login_from',
            
            // Amount range filters
            'total_spent_min' => 'nullable|numeric|min:0',
            'total_spent_max' => 'nullable|numeric|min:0',
            'total_bookings_min' => 'nullable|integer|min:0',
            'total_bookings_max' => 'nullable|integer|min:0',
            
            // Search and sorting
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|in:created_at,updated_at,first_name,last_name,total_spent,total_bookings,last_login_at,email',
            'sort_direction' => 'nullable|in:asc,desc',
            
            // Pagination
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);
    }
}