<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseApiController;
use App\Models\Quote;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Route;
use App\Services\QuoteService;
use App\Repositories\Contracts\QuoteRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Quote Controller
 * 
 * Handles quote management with status tracking, quote to booking conversion
 * with data integrity, and quote expiry handling with automated notifications.
 * 
 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5
 */
class QuoteController extends BaseApiController
{
    public function __construct(
        private QuoteService $quoteService,
        private QuoteRepositoryInterface $quoteRepository
    ) {}

    /**
     * Display a listing of quotes with filtering and status tracking
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Debug: Check if user is authenticated and determine user type
            $user = $request->user();
            $isCustomer = $user && $user instanceof \App\Models\Customer;
            $isAdmin = $user && $user instanceof \App\Models\User;
            
            Log::info('Quote Index Request Debug', [
                'user_exists' => !is_null($user),
                'user_type' => $user ? get_class($user) : 'null',
                'user_id' => $user ? $user->id : 'null',
                'is_customer' => $isCustomer,
                'is_admin' => $isAdmin,
                'request_path' => $request->path(),
                'auth_guard' => auth()->getDefaultDriver(),
                'sanctum_user' => auth('sanctum')->user() ? get_class(auth('sanctum')->user()) : 'null'
            ]);
            
            // If accessed by authenticated customer, get their quotes directly
            if ($isCustomer) {
                $customerId = $user->id;
                $quotes = $this->quoteRepository->getByCustomer($customerId);
                
                // Load relationships for customer quotes
                $quotes->load(['customer', 'route']);
                
                // Transform quotes to add computed fields
                $quotesData = $quotes->map(function ($quote) {
                    $quoteData = $quote->toArray();
                    $quoteData['is_expired'] = $quote->is_expired;
                    $quoteData['is_valid'] = $quote->is_valid;
                    $quoteData['days_until_expiry'] = $quote->days_until_expiry;
                    $quoteData['status_label'] = $quote->status_label;
                    $quoteData['vehicle_description'] = $quote->vehicle_description;
                    $quoteData['total_fees'] = $quote->total_fees;
                    return $quoteData;
                });
                
                return $this->successResponse([
                    'data' => $quotesData,
                    'meta' => [
                        'total' => $quotes->count(),
                        'customer_id' => $customerId
                    ]
                ], 'Quotes retrieved successfully');
            }
            
            // For admin users or unauthenticated requests, use the full filtering system
            $filters = $this->validateFilters($request);
            
            Log::info('Admin Quote Filters Applied', [
                'filters' => $filters,
                'per_page' => $request->get('per_page', 15)
            ]);
            
            // Get filtered quotes with pagination and relationships
            $quotes = $this->quoteRepository->getFilteredPaginatedWithRelations($filters, $request->get('per_page', 15));
            
            Log::info('Quotes Retrieved', [
                'total_quotes' => $quotes->total(),
                'current_page' => $quotes->currentPage(),
                'per_page' => $quotes->perPage(),
                'quotes_count' => $quotes->count()
            ]);
            
            // Add status indicators and computed fields
            $quotes->getCollection()->transform(function ($quote) {
                $quoteData = $quote->toArray();
                $quoteData['is_expired'] = $quote->is_expired;
                $quoteData['is_valid'] = $quote->is_valid;
                $quoteData['days_until_expiry'] = $quote->days_until_expiry;
                $quoteData['status_label'] = $quote->status_label;
                $quoteData['vehicle_description'] = $quote->vehicle_description;
                $quoteData['total_fees'] = $quote->total_fees;
                
                // Add formatted customer name for easier frontend access
                if ($quote->customer) {
                    $quoteData['customer_name'] = $quote->customer->full_name;
                    $quoteData['customer_email'] = $quote->customer->email;
                }
                
                // Add route information for easier frontend access
                if ($quote->route) {
                    $quoteData['origin_country'] = $quote->route->origin_country;
                    $quoteData['destination_country'] = $quote->route->destination_country;
                    $quoteData['route_description'] = $quote->route->full_route;
                }
                
                // Add vehicle information from vehicle_details JSON
                if ($quote->vehicle_details) {
                    $vehicleDetails = $quote->vehicle_details;
                    $quoteData['vehicle_make'] = $vehicleDetails['make'] ?? 'N/A';
                    $quoteData['vehicle_model'] = $vehicleDetails['model'] ?? 'N/A';
                    $quoteData['vehicle_year'] = $vehicleDetails['year'] ?? 'N/A';
                    $quoteData['vehicle_type'] = $vehicleDetails['vehicle_type'] ?? 'N/A';
                    $quoteData['vehicle_full_description'] = sprintf(
                        '%s %s %s',
                        $vehicleDetails['year'] ?? '',
                        $vehicleDetails['make'] ?? '',
                        $vehicleDetails['model'] ?? ''
                    );
                }
                
                return $quoteData;
            });
            
            return $this->paginatedResponse($quotes, 'Quotes retrieved successfully');
            
        } catch (ValidationException $e) {
            Log::error('Quote Index Validation Error', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            Log::error('Quote Index Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created quote
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, Quote::validationRules());
            
            $quote = $this->quoteService->createQuote($validatedData);
            
            return $this->createdResponse($quote->load([
                'customer', 'route', 'createdBy'
            ]), 'Quote created successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified quote
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $quote = $this->quoteRepository->findWithRelations($id, [
                'customer', 'route', 'bookings', 'createdBy', 'approvedBy'
            ]);
            
            if (!$quote) {
                return $this->notFoundResponse('Quote');
            }
            
            // Add computed fields
            $quoteData = $quote->toArray();
            $quoteData['is_expired'] = $quote->is_expired;
            $quoteData['is_valid'] = $quote->is_valid;
            $quoteData['days_until_expiry'] = $quote->days_until_expiry;
            $quoteData['status_label'] = $quote->status_label;
            $quoteData['vehicle_description'] = $quote->vehicle_description;
            $quoteData['total_fees'] = $quote->total_fees;
            
            $this->logActivity('quote_viewed', Quote::class, $id);
            
            return $this->successResponse($quoteData, 'Quote retrieved successfully');
            
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified quote
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $quote = $this->quoteRepository->find($id);
            if (!$quote) {
                return $this->notFoundResponse('Quote');
            }
            
            // Prevent updates to converted quotes
            if ($quote->status === Quote::STATUS_CONVERTED) {
                return $this->errorResponse('Cannot update converted quotes', 400);
            }
            
            $validatedData = $this->validateRequest($request, Quote::validationRules());
            
            $updatedQuote = $this->quoteRepository->update($id, $validatedData);
            
            return $this->updatedResponse($updatedQuote->load([
                'customer', 'route'
            ]), 'Quote updated successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Approve a quote and send login credentials to customer
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'notes' => 'nullable|string|max:1000'
            ]);
            
            $quote = $this->quoteRepository->find($id);
            if (!$quote) {
                return $this->notFoundResponse('Quote');
            }
            
            if ($quote->status !== Quote::STATUS_PENDING) {
                return $this->errorResponse('Only pending quotes can be approved', 400);
            }
            
            // Get the customer
            $customer = Customer::find($quote->customer_id);
            if (!$customer) {
                return $this->errorResponse('Customer not found', 404);
            }
            
            // Generate temporary password if customer doesn't have one or has a system-generated one
            // In development mode, always generate a new temporary password for easier testing
            $temporaryPassword = null;
            if (!$customer->password || $customer->password_is_temporary || config('app.env') === 'local') {
                $temporaryPassword = Str::random(12); // Generate 12-character password
                $customer->password = Hash::make($temporaryPassword);
                $customer->password_is_temporary = true;
                $customer->save();
                
                Log::info('🔑 TEMPORARY PASSWORD GENERATED (Development)', [
                    'customer_email' => $customer->email,
                    'customer_name' => $customer->first_name . ' ' . $customer->last_name,
                    'temporary_password' => $temporaryPassword,
                    'quote_reference' => $quote->quote_reference
                ]);
            }
            
            $success = $quote->approve(auth()->id(), $validatedData['notes'] ?? null);
            
            if ($success) {
                // Store temporary password in session for later use when converting to booking
                if ($temporaryPassword) {
                    session(['quote_' . $quote->id . '_temp_password' => $temporaryPassword]);
                }
                
                $this->logActivity('quote_approved', Quote::class, $id, [
                    'notes' => $validatedData['notes'] ?? null,
                    'credentials_generated' => !empty($temporaryPassword)
                ]);
                
                return $this->updatedResponse($quote->fresh()->load(['customer']), 'Quote approved successfully. Customer will be notified when converted to booking.');
            }
            
            return $this->errorResponse('Failed to approve quote');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Reject a quote
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'reason' => 'required|string|max:1000'
            ]);
            
            $quote = $this->quoteRepository->find($id);
            if (!$quote) {
                return $this->notFoundResponse('Quote');
            }
            
            if ($quote->status !== Quote::STATUS_PENDING) {
                return $this->errorResponse('Only pending quotes can be rejected', 400);
            }
            
            $success = $quote->reject($validatedData['reason'], auth()->id());
            
            if ($success) {
                $this->logActivity('quote_rejected', Quote::class, $id, [
                    'reason' => $validatedData['reason']
                ]);
                
                return $this->updatedResponse($quote->fresh()->load(['customer']), 'Quote rejected successfully');
            }
            
            return $this->errorResponse('Failed to reject quote');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Convert quote to booking with data integrity
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function convertToBooking(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'pickup_date' => 'nullable|date|after_or_equal:today',
                'delivery_date' => 'nullable|date|after:pickup_date',
                'estimated_delivery' => 'nullable|date|after:pickup_date',
                'recipient_name' => 'nullable|string|max:255',
                'recipient_phone' => 'nullable|string|max:20',
                'recipient_email' => 'nullable|email|max:255',
                'recipient_address' => 'nullable|string|max:500',
                'notes' => 'nullable|string|max:1000'
            ]);
            
            $booking = $this->quoteService->convertQuoteToBooking($id, $validatedData);
            
            // Get the quote and customer
            $quote = $booking->quote;
            $customer = $quote->customer;
            
            // Get temporary password from session or generate new one
            $temporaryPassword = session('quote_' . $quote->id . '_temp_password');
            if (!$temporaryPassword && (!$customer->password || $customer->password_is_temporary)) {
                $temporaryPassword = Str::random(12);
                $customer->password = Hash::make($temporaryPassword);
                $customer->password_is_temporary = true;
                $customer->save();
                
                Log::info('🔑 TEMPORARY PASSWORD GENERATED (Convert to Booking)', [
                    'customer_email' => $customer->email,
                    'temporary_password' => $temporaryPassword,
                    'quote_reference' => $quote->quote_reference
                ]);
            }
            
            // Send quote approved email with login credentials NOW (when converting to booking)
            if ($temporaryPassword) {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->sendQuoteApprovedNotification($quote, $temporaryPassword);
                
                // Clear the session password
                session()->forget('quote_' . $quote->id . '_temp_password');
                
                Log::info('📧 Quote Approved Email Sent on Booking Conversion', [
                    'quote_id' => $quote->id,
                    'booking_id' => $booking->id,
                    'customer_email' => $customer->email
                ]);
            }
            
            return $this->createdResponse($booking->load([
                'customer', 'quote', 'vehicle', 'route'
            ]), 'Quote converted to booking successfully. Customer has been notified with login credentials.');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Extend quote validity
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function extendValidity(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'days' => 'required|integer|min:1|max:365'
            ]);
            
            $quote = $this->quoteRepository->find($id);
            if (!$quote) {
                return $this->notFoundResponse('Quote');
            }
            
            if ($quote->status === Quote::STATUS_CONVERTED) {
                return $this->errorResponse('Cannot extend validity of converted quotes', 400);
            }
            
            $success = $quote->extendValidity($validatedData['days']);
            
            if ($success) {
                $this->logActivity('quote_validity_extended', Quote::class, $id, [
                    'days_extended' => $validatedData['days'],
                    'new_valid_until' => $quote->valid_until
                ]);
                
                return $this->updatedResponse($quote->fresh(), 'Quote validity extended successfully');
            }
            
            return $this->errorResponse('Failed to extend quote validity');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get quotes requiring approval
     * 
     * @return JsonResponse
     */
    public function requiresApproval(): JsonResponse
    {
        try {
            $quotes = $this->quoteRepository->getRequiringApproval();
            
            return $this->collectionResponse($quotes, 'Quotes requiring approval retrieved successfully');
            
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get quotes expiring soon
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function expiringSoon(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 7);
            $quotes = $this->quoteRepository->getExpiringSoon($days);
            
            return $this->collectionResponse($quotes, 'Quotes expiring soon retrieved successfully');
            
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handle quote expiry and automated notifications
     * 
     * @return JsonResponse
     */
    public function processExpiredQuotes(): JsonResponse
    {
        try {
            $expiredQuotes = $this->quoteRepository->getExpired();
            $processedCount = 0;
            
            foreach ($expiredQuotes as $quote) {
                if ($quote->checkAndUpdateExpiry()) {
                    $processedCount++;
                }
            }
            
            $this->logActivity('quotes_expiry_processed', null, null, [
                'processed_count' => $processedCount
            ]);
            
            return $this->successResponse([
                'processed_count' => $processedCount,
                'total_expired' => $expiredQuotes->count()
            ], 'Quote expiry processing completed');
            
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get quote statistics and conversion metrics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $statistics = $this->quoteRepository->getConversionStatistics();
            
            return $this->successResponse($statistics, 'Quote statistics retrieved successfully');
            
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Search quotes
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
            
            $quotes = $this->quoteRepository->searchQuotes($validatedData['query']);
            
            return $this->collectionResponse($quotes, 'Search results retrieved successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get quote analytics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date'
            ]);
            
            $analytics = $this->quoteRepository->getAnalyticsByDateRange(
                $validatedData['start_date'],
                $validatedData['end_date']
            );
            
            return $this->successResponse($analytics, 'Quote analytics retrieved successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Legacy create method for backward compatibility
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        Log::info('Quote Create Request:', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                // Vehicle
                'vehicleType' => 'required|string',
                'year' => 'required|integer|min:1900|max:2026',
                'make' => 'required|string|max:100',
                'model' => 'nullable|string|max:100',
                'engineSize' => 'nullable|numeric',
                
                // Shipping
                'originCountry' => 'required|string',
                'originPort' => 'nullable|string',
                'shippingMethod' => 'required|in:roro,container',
                
                // Personal
                'fullName' => 'required|string|max:200',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'deliveryLocation' => 'nullable|string',
                'additionalInfo' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::warning('Quote Validation Failed:', $validator->errors()->toArray());
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // 1. Find or Create Customer (Guest handling)
            $names = explode(' ', $request->fullName, 2);
            $firstName = $names[0];
            $lastName = $names[1] ?? '';

            $customer = Customer::firstOrCreate(
                ['email' => $request->email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $request->phone,
                    'password' => Hash::make(Str::random(16)), 
                    'is_active' => true,
                    'is_verified' => false,
                ]
            );

            // 2. Find Route
            $originMap = [
                'japan' => 'Japan',
                'uk' => 'United Kingdom',
                'uae' => 'UAE',
            ];
            
            $originCountry = $originMap[strtolower($request->originCountry)] ?? $request->originCountry;

            $route = Route::where('origin_country', 'like', "%{$originCountry}%")
                        ->where('destination_country', 'Uganda')
                        ->first();

            if (!$route) {
                Log::error("Route not found for origin: $originCountry");
                return response()->json(['message' => 'Shipping route not available.'], 400);
            }

            // 3. Map vehicle type to ID (1 = Sedan/Car, 2 = SUV/Truck)
            $vehicleTypeMap = [
                'sedan' => 1,
                'car' => 1,
                'suv' => 2,
                'truck' => 2,
                'van' => 2,
                'luxury' => 1,
                'motorcycle' => 1,
            ];
            $vehicleTypeId = $vehicleTypeMap[strtolower($request->vehicleType)] ?? 1;
            
            Log::info("Vehicle Type Mapping: {$request->vehicleType} -> {$vehicleTypeId}");

            // 3. Create Vehicle
            $vehicleData = [
                'vehicle_type_id' => (int)$vehicleTypeId, 
                'make' => $request->make,
                'model' => $request->model ?? 'Unknown',
                'year' => (int)$request->year,
                'description' => "Engine Size: " . ($request->engineSize ?? 'N/A'),
                'is_running' => true,
            ];
            
            Log::info("Creating vehicle with data: " . json_encode($vehicleData));
            
            $vehicle = Vehicle::create($vehicleData);

            // 4. Calculate Price
            $shippingCost = $route->base_price;
            if ($request->shippingMethod === 'container') {
                $shippingCost += 500;
            }
            
            $customsDuty = 800;
            $vat = ($shippingCost + $customsDuty) * 0.18;
            $levies = 350;
            $total = $shippingCost + $customsDuty + $vat + $levies;

            // 5. Create Quote
            try {
                $quote = Quote::create([
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicle->id,
                    'vehicle_details' => [
                        'make' => $request->make,
                        'model' => $request->model ?? 'Unknown',
                        'year' => $request->year,
                        'engine_size' => $request->engineSize ?? null,
                        'vehicle_type' => $request->vehicleType
                    ],
                    'route_id' => $route->id,
                    'base_price' => $shippingCost,
                    'additional_fees' => [
                        ['name' => 'Customs Duty', 'amount' => $customsDuty],
                        ['name' => 'VAT', 'amount' => $vat],
                        ['name' => 'Levies', 'amount' => $levies]
                    ],
                    'total_amount' => $total,
                    'status' => 'pending',
                    'valid_until' => now()->addDays(30),
                ]);

                Log::info("Quote created successfully: ID {$quote->id}, Customer: {$customer->id}, Route: {$route->id}");

                // Send quote created notification
                try {
                    $notificationService = app(\App\Services\NotificationService::class);
                    $notificationService->sendQuoteCreatedNotification($quote);
                    Log::info("Quote created notification sent for quote ID: {$quote->id}");
                } catch (Exception $notificationError) {
                    Log::error("Failed to send quote created notification: " . $notificationError->getMessage());
                    // Don't fail the quote creation if notification fails
                }

                return response()->json([
                    'message' => 'Quote created successfully',
                    'quote_id' => $quote->id,
                    'reference' => $quote->quote_reference ?? 'QT-' . str_pad($quote->id, 5, '0', STR_PAD_LEFT),
                    'total_estimated' => $total
                ], 201);
            } catch (\Exception $quoteError) {
                Log::error("Quote Creation Failed after vehicle creation: " . $quoteError->getMessage());
                Log::error("Quote Error Trace: " . $quoteError->getTraceAsString());
                throw $quoteError; // Re-throw to be caught by outer catch
            }

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error("Quote Creation Database Error: " . $e->getMessage());
            Log::error("SQL Error: " . $e->getSql());
            Log::error("Bindings: " . json_encode($e->getBindings()));
            return response()->json([
                'message' => 'Database error occurred while creating quote',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        } catch (\Exception $e) {
            Log::error("Quote Creation Error: " . $e->getMessage());
            Log::error("Error Trace: " . $e->getTraceAsString());
            return response()->json([
                'message' => 'Internal Server Error',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Legacy lookup method for backward compatibility
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function lookup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Parse ID from Reference (QT2026010001 -> extract the sequence number)
        $reference = strtoupper($request->reference);
        if (!Str::startsWith($reference, 'QT')) {
            return response()->json(['message' => 'Invalid reference format.'], 400);
        }
        
        // Find quote by exact reference match instead of parsing ID
        $quote = Quote::with(['route', 'customer'])
            ->where('quote_reference', $reference)
            ->whereHas('customer', function ($query) use ($request) {
                $query->where('email', $request->email);
            })
            ->first();

        if (!$quote) {
            return response()->json(['message' => 'Quote not found or email does not match.'], 404);
        }
        
        // Add reference to response again just in case
        $quoteData = $quote->toArray();
        $quoteData['reference'] = $reference;

        return response()->json($quoteData);
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
            'status' => 'nullable|in:' . implode(',', Quote::VALID_STATUSES),
            'customer_id' => 'nullable|integer|exists:customers,id',
            'route_id' => 'nullable|integer|exists:routes,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string|max:100',
            'expiring_soon' => 'nullable|boolean',
            'amount_min' => 'nullable|numeric|min:0',
            'amount_max' => 'nullable|numeric|min:0',
            'vehicle_make' => 'nullable|string|max:100',
            'vehicle_year' => 'nullable|integer|min:1900|max:2030',
            'sort_by' => 'nullable|in:created_at,updated_at,total_amount,valid_until',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);
    }
}
