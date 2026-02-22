<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseApiController;
use App\Models\Booking;
use App\Models\Quote;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * Booking Controller
 * 
 * Handles all CRUD operations for bookings including listing with advanced filtering,
 * creation, updates, status management, and deletion with confirmation and audit trails.
 * 
 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5
 */
class BookingController extends BaseApiController
{
    public function __construct(
        private BookingService $bookingService,
        private BookingRepositoryInterface $bookingRepository
    ) {}

    /**
     * Display a listing of bookings with advanced filtering
     * 
     * Supports filtering by status, date range, customer, route, payment status,
     * and search across booking reference and customer details.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // If accessed by authenticated customer, get their bookings directly
            if ($request->user() && $request->user() instanceof \App\Models\Customer) {
                $bookings = $this->bookingRepository->getByCustomer($request->user()->id);
                
                return $this->successResponse([
                    'data' => $bookings,
                    'meta' => [
                        'total' => $bookings->count(),
                        'customer_id' => $request->user()->id
                    ]
                ], 'Bookings retrieved successfully');
            }
            
            // For admin users, use the full filtering system
            $filters = $this->validateFilters($request);
            
            // Get filtered bookings with pagination
            $bookings = $this->bookingRepository->getFilteredPaginated($filters, $request->get('per_page', 15));
            
            return $this->paginatedResponse($bookings, 'Bookings retrieved successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created booking (Admin endpoint)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, Booking::validationRules());
            $booking = $this->bookingService->createBooking($validatedData);
            return $this->createdResponse($booking->load([
                'customer', 'quote', 'vehicle', 'route', 'shipment'
            ]), 'Booking created successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Create booking from quote confirmation (Customer endpoint)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'quote_reference' => 'required|string',
                'email' => 'required|email',
                'id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
                'logbook_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120'
            ]);
            
            // Find the quote by reference and validate customer email
            $quote = Quote::where('quote_reference', $validatedData['quote_reference'])
                ->whereHas('customer', function ($query) use ($validatedData) {
                    $query->where('email', $validatedData['email']);
                })
                ->with(['customer', 'route'])
                ->first();
            
            if (!$quote) {
                return response()->json(['message' => 'Quote not found or email does not match'], 404);
            }
            
            // Validate quote can be converted
            if ($quote->status !== Quote::STATUS_APPROVED) {
                return response()->json(['message' => 'Only approved quotes can be converted to bookings'], 400);
            }
            
            if ($quote->is_expired) {
                return response()->json(['message' => 'Cannot convert expired quote'], 400);
            }
            
            // Check if quote is already converted
            $existingBooking = Booking::where('quote_id', $quote->id)->first();
            if ($existingBooking) {
                return response()->json(['message' => 'Quote has already been converted to booking: ' . $existingBooking->booking_reference], 400);
            }
            
            // Prepare booking data
            $bookingData = [
                'customer_id' => $quote->customer_id,
                'quote_id' => $quote->id,
                'route_id' => $quote->route_id,
                'total_amount' => $quote->total_amount,
                'currency' => $quote->currency,
                'notes' => $quote->notes,
                'status' => Booking::STATUS_CONFIRMED,
                // Required fields from original migration
                'pickup_date' => now()->addDays(7), // Default to 1 week from now
                'recipient_name' => $quote->customer->first_name . ' ' . $quote->customer->last_name,
                'recipient_phone' => $quote->customer->phone ?? 'N/A',
                'recipient_email' => $quote->customer->email,
                'recipient_country' => $quote->customer->country ?? 'Uganda',
                'recipient_city' => $quote->customer->city ?? 'Kampala',
                'recipient_address' => $quote->customer->address ?? 'Address to be provided',
            ];
            
            // Create vehicle from quote vehicle_details
            if ($quote->vehicle_details) {
                $vehicle = Vehicle::create([
                    'vehicle_type_id' => 1, // Default vehicle type
                    'make' => $quote->vehicle_details['make'] ?? '',
                    'model' => $quote->vehicle_details['model'] ?? '',
                    'year' => $quote->vehicle_details['year'] ?? date('Y'),
                    'engine_type' => 'petrol', // Default
                    'transmission' => 'automatic', // Default
                    'color' => $quote->vehicle_details['color'] ?? null,
                ]);
                
                $bookingData['vehicle_id'] = $vehicle->id;
            } else {
                // Create a default vehicle if no details available
                $vehicle = Vehicle::create([
                    'vehicle_type_id' => 1, // Default vehicle type
                    'make' => 'Unknown',
                    'model' => 'Unknown',
                    'year' => date('Y'),
                    'engine_type' => 'petrol',
                    'transmission' => 'automatic',
                ]);
                
                $bookingData['vehicle_id'] = $vehicle->id;
            }
            
            // Create the booking
            $booking = Booking::create($bookingData);
            
            // Handle document uploads if provided
            if ($request->hasFile('id_document') || $request->hasFile('logbook_document')) {
                $this->handleDocumentUploads($request, $booking);
            }
            
            // Update quote status to converted
            $quote->status = Quote::STATUS_CONVERTED;
            $quote->save();
            
            return response()->json([
                'message' => 'Booking confirmed successfully',
                'data' => $booking->load(['customer', 'quote', 'route'])
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handle document uploads for booking
     */
    private function handleDocumentUploads(Request $request, Booking $booking): void
    {
        try {
            \Illuminate\Support\Facades\Log::info('Starting document upload process', [
                'booking_id' => $booking->id,
                'has_id_document' => $request->hasFile('id_document'),
                'has_logbook_document' => $request->hasFile('logbook_document')
            ]);

            if ($request->hasFile('id_document')) {
                $file = $request->file('id_document');
                $filename = 'id_document_' . $booking->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('documents/bookings/' . $booking->id, $filename, 'public');
                
                \Illuminate\Support\Facades\Log::info('ID document uploaded', [
                    'booking_id' => $booking->id,
                    'filename' => $filename,
                    'path' => $path,
                    'size' => $file->getSize()
                ]);
                
                // Create document record
                $document = \App\Models\Document::create([
                    'booking_id' => $booking->id,
                    'customer_id' => $booking->customer_id,
                    'document_type' => 'passport', // Map id_document to passport type
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'status' => 'pending' // Use correct status value
                ]);
                
                \Illuminate\Support\Facades\Log::info('ID document record created', [
                    'document_id' => $document->id,
                    'booking_id' => $booking->id
                ]);
            }
            
            if ($request->hasFile('logbook_document')) {
                $file = $request->file('logbook_document');
                $filename = 'logbook_document_' . $booking->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('documents/bookings/' . $booking->id, $filename, 'public');
                
                \Illuminate\Support\Facades\Log::info('Logbook document uploaded', [
                    'booking_id' => $booking->id,
                    'filename' => $filename,
                    'path' => $path,
                    'size' => $file->getSize()
                ]);
                
                // Create document record
                $document = \App\Models\Document::create([
                    'booking_id' => $booking->id,
                    'customer_id' => $booking->customer_id,
                    'document_type' => 'other', // Map logbook_document to other type
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'status' => 'pending' // Use correct status value
                ]);
                
                \Illuminate\Support\Facades\Log::info('Logbook document record created', [
                    'document_id' => $document->id,
                    'booking_id' => $booking->id
                ]);
            }
            
            \Illuminate\Support\Facades\Log::info('Document upload process completed', [
                'booking_id' => $booking->id
            ]);
            
        } catch (Exception $e) {
            // Log the error but don't fail the booking creation
            \Illuminate\Support\Facades\Log::error('Document upload failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Display the specified booking
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $booking = $this->bookingRepository->findWithRelations($id, [
                'customer', 'quote', 'vehicle', 'route', 'shipment', 
                'documents', 'payments', 'createdBy', 'updatedBy'
            ]);
            
            if (!$booking) {
                return $this->notFoundResponse('Booking');
            }
            
            // Add computed fields
            $bookingData = $booking->toArray();
            $bookingData['balance_amount'] = $booking->balance_amount;
            $bookingData['payment_status'] = $booking->payment_status;
            $bookingData['is_overdue'] = $booking->is_overdue;
            $bookingData['progress'] = $booking->calculateProgress();
            $bookingData['missing_documents'] = $booking->getMissingDocuments();
            
            $this->logActivity('booking_viewed', Booking::class, $id);
            
            return $this->successResponse($bookingData, 'Booking retrieved successfully');
            
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified booking
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $booking = $this->bookingRepository->find($id);
            if (!$booking) {
                return $this->notFoundResponse('Booking');
            }
            $validatedData = $this->validateRequest($request, Booking::updateValidationRules($id));
            $updatedBooking = $this->bookingService->updateBooking($id, $validatedData);
            return $this->updatedResponse($updatedBooking->load([
                'customer', 'quote', 'vehicle', 'route', 'shipment'
            ]), 'Booking updated successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update booking status with validation
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'status' => 'required|in:' . implode(',', Booking::VALID_STATUSES),
                'reason' => 'nullable|string|max:500'
            ]);
            
            $booking = $this->bookingService->updateBookingStatus(
                $id, 
                $validatedData['status'], 
                $validatedData['reason'] ?? null
            );
            
            return $this->updatedResponse($booking->load([
                'customer', 'shipment'
            ]), 'Booking status updated successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified booking with confirmation and audit trail
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'reason' => 'nullable|string|max:500',
                'confirmation' => 'nullable|boolean'
            ]);
            $reason = $validatedData['reason'] ?? 'Deleted by admin';
            $deleted = $this->bookingService->deleteBooking($id, $reason);
            if ($deleted) {
                return $this->deletedResponse('Booking deleted successfully');
            }
            return $this->errorResponse('Failed to delete booking');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get booking statistics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $filters = $this->validateFilters($request);
            $statistics = $this->bookingService->getBookingStatistics($filters);
            
            return $this->successResponse($statistics, 'Booking statistics retrieved successfully');
            
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get bookings requiring attention
     * 
     * @return JsonResponse
     */
    public function requiresAttention(): JsonResponse
    {
        try {
            $bookings = $this->bookingService->getBookingsRequiringAttention();
            
            return $this->collectionResponse($bookings, 'Bookings requiring attention retrieved successfully');
            
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Search bookings
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'query' => 'required|string|min:2|max:100'
            ]);
            
            $bookings = $this->bookingRepository->searchBookings($validatedData['query']);
            
            return $this->collectionResponse($bookings, 'Search results retrieved successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Process payment for booking
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function processPayment(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|in:bank_transfer,mobile_money,credit_card,cash',
                'notes' => 'nullable|string|max:500'
            ]);
            
            $success = $this->bookingService->processPayment(
                $id, 
                $validatedData['amount'], 
                $validatedData['payment_method']
            );
            
            if ($success) {
                $booking = $this->bookingRepository->findWithRelations($id, ['payments']);
                return $this->successResponse($booking, 'Payment processed successfully');
            }
            
            return $this->errorResponse('Failed to process payment');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get booking analytics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'group_by' => 'nullable|in:day,week,month,year'
            ]);
            
            $analytics = $this->bookingRepository->getAnalyticsByDateRange(
                $validatedData['start_date'],
                $validatedData['end_date']
            );
            
            return $this->successResponse($analytics, 'Booking analytics retrieved successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Validate filter parameters
     * 
     * @param Request $request
     * @return array
     */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'status' => 'nullable|in:' . implode(',', Booking::VALID_STATUSES),
            'customer_id' => 'nullable|integer|exists:customers,id',
            'route_id' => 'nullable|integer|exists:routes,id',
            'payment_status' => 'nullable|in:paid,partial,unpaid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string|max:100',
            'overdue' => 'nullable|boolean',
            'amount_min' => 'nullable|numeric|min:0',
            'amount_max' => 'nullable|numeric|min:0',
            'sort_by' => 'nullable|in:created_at,updated_at,total_amount,pickup_date,delivery_date',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);
    }
}
