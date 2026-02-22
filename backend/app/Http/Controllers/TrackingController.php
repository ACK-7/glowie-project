<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseApiController;
use App\Services\ShipmentService;
use App\Services\MapTrackingService;
use App\Repositories\Contracts\ShipmentRepositoryInterface;
use App\Http\Requests\ShipmentRequest;
use App\Http\Requests\UpdateShipmentStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Shipment Controller
 * 
 * Handles shipment management with real-time status updates,
 * delay detection, and completion workflow triggers.
 * 
 * Requirements: 5.1, 5.2, 5.4, 5.5
 */
class TrackingController extends BaseApiController
{
    public function __construct(
        private ShipmentService $shipmentService,
        private ShipmentRepositoryInterface $shipmentRepository,
        private MapTrackingService $mapTrackingService
    ) {}

    /**
     * Display a listing of shipments with filtering options
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status', 'carrier_name', 'departure_port', 'arrival_port',
                'overdue', 'delayed', 'booking_id', 'search'
            ]);
            
            $perPage = $request->get('per_page', 15);
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $shipments = $this->shipmentRepository->getFilteredPaginated(
                $filters,
                $perPage,
                ['booking.customer', 'booking.vehicle', 'booking.route'],
                $sortBy,
                $sortOrder
            );
            
            return $this->paginatedResponse($shipments, 'Shipments retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve shipments', [
                'error' => $e->getMessage(),
                'filters' => $filters ?? []
            ]);
            
            return $this->errorResponse('Failed to retrieve shipments', 500);
        }
    }

    /**
     * Store a newly created shipment
     * 
     * @param ShipmentRequest $request
     * @return JsonResponse
     */
    public function store(ShipmentRequest $request): JsonResponse
    {
        try {
            $shipment = $this->shipmentService->createShipment($request->validated());
            
            return $this->successResponse(
                $shipment->load(['booking.customer', 'booking.vehicle']),
                'Shipment created successfully',
                201
            );
            
        } catch (Exception $e) {
            Log::error('Failed to create shipment', [
                'error' => $e->getMessage(),
                'data' => $request->validated()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Display the specified shipment with tracking information
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $shipment = $this->shipmentRepository->findWithRelations($id, [
                'booking.customer', 'booking.vehicle', 'booking.route', 'booking.documents', 'booking.payments'
            ]);
            
            if (!$shipment) {
                return $this->errorResponse('Shipment not found', 404);
            }
            
            // Get comprehensive tracking information
            $trackingData = [
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
            
            return $this->successResponse($trackingData, 'Shipment details retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve shipment details', [
                'shipment_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve shipment details', 500);
        }
    }

    /**
     * Update the specified shipment
     * 
     * @param ShipmentRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(ShipmentRequest $request, int $id): JsonResponse
    {
        try {
            $shipment = $this->shipmentService->updateShipment($id, $request->validated());
            
            return $this->successResponse(
                $shipment->load(['booking.customer', 'booking.vehicle']),
                'Shipment updated successfully'
            );
            
        } catch (Exception $e) {
            Log::error('Failed to update shipment', [
                'shipment_id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->validated()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Update shipment status with validation and workflow triggers
     * 
     * @param UpdateShipmentStatusRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(UpdateShipmentStatusRequest $request, int $id): JsonResponse
    {
        try {
            $shipment = $this->shipmentService->updateShipmentStatus(
                $id,
                $request->validated('status'),
                $request->validated('location'),
                $request->validated('notes')
            );
            
            return $this->successResponse(
                $shipment->load(['booking.customer']),
                'Shipment status updated successfully'
            );
            
        } catch (Exception $e) {
            Log::error('Failed to update shipment status', [
                'shipment_id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->validated()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get shipment tracking information by tracking number
     * 
     * @param string $trackingNumber
     * @return JsonResponse
     */
    public function track(string $trackingNumber): JsonResponse
    {
        try {
            $trackingData = $this->shipmentService->getShipmentTracking($trackingNumber);
            
            return $this->successResponse($trackingData, 'Tracking information retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve tracking information', [
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Update estimated arrival date with workflow
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateEstimatedArrival(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'estimated_arrival' => 'required|date|after:today',
            'reason' => 'nullable|string|max:500'
        ]);
        
        try {
            $shipment = $this->shipmentService->updateEstimatedArrival(
                $id,
                \Carbon\Carbon::parse($request->estimated_arrival),
                $request->reason
            );
            
            return $this->successResponse(
                $shipment->load(['booking.customer']),
                'Estimated arrival updated successfully'
            );
            
        } catch (Exception $e) {
            Log::error('Failed to update estimated arrival', [
                'shipment_id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->only(['estimated_arrival', 'reason'])
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get shipments requiring attention (delayed, overdue, stuck in customs)
     * 
     * @return JsonResponse
     */
    public function requiresAttention(): JsonResponse
    {
        try {
            $shipments = $this->shipmentService->getShipmentsRequiringAttention();
            
            return $this->successResponse($shipments, 'Shipments requiring attention retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve shipments requiring attention', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve shipments requiring attention', 500);
        }
    }

    /**
     * Get shipment statistics and analytics
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->shipmentService->getShipmentStatistics();
            
            return $this->successResponse($statistics, 'Shipment statistics retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve shipment statistics', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve shipment statistics', 500);
        }
    }

    /**
     * Process delayed shipments automatically
     * 
     * @return JsonResponse
     */
    public function processDelayed(): JsonResponse
    {
        try {
            $processedShipments = $this->shipmentService->processDelayedShipments();
            
            return $this->successResponse([
                'processed_count' => count($processedShipments),
                'processed_shipments' => $processedShipments
            ], 'Delayed shipments processed successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to process delayed shipments', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to process delayed shipments', 500);
        }
    }

    /**
     * Get delivery performance metrics
     * 
     * @return JsonResponse
     */
    public function deliveryPerformance(): JsonResponse
    {
        try {
            $performance = $this->shipmentRepository->getDeliveryPerformanceMetrics();
            
            return $this->successResponse($performance, 'Delivery performance metrics retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve delivery performance metrics', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve delivery performance metrics', 500);
        }
    }

    /**
     * Get carrier performance analysis
     * 
     * @return JsonResponse
     */
    public function carrierPerformance(): JsonResponse
    {
        try {
            $performance = $this->shipmentRepository->getCarrierPerformanceAnalysis();
            
            return $this->successResponse($performance, 'Carrier performance analysis retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve carrier performance analysis', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve carrier performance analysis', 500);
        }
    }

    /**
     * Search shipments across multiple fields
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100'
        ]);
        
        try {
            $shipments = $this->shipmentRepository->searchShipments($request->query);
            
            return $this->successResponse($shipments, 'Search results retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to search shipments', [
                'query' => $request->query,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to search shipments', 500);
        }
    }

    /**
     * Get recent shipments
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $shipments = $this->shipmentRepository->getRecent($limit);
            
            return $this->successResponse($shipments, 'Recent shipments retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve recent shipments', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve recent shipments', 500);
        }
    }

    /**
     * Get shipment trends for analytics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function trends(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365'
        ]);
        
        try {
            $days = $request->get('days', 30);
            $trends = $this->shipmentRepository->getShipmentTrends($days);
            
            return $this->successResponse($trends, 'Shipment trends retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve shipment trends', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve shipment trends', 500);
        }
    }

    /**
     * Remove the specified shipment (soft delete)
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $shipment = $this->shipmentRepository->findOrFail($id);
            
            // Prevent deletion of delivered shipments
            if ($shipment->status === 'delivered') {
                return $this->errorResponse('Cannot delete delivered shipment', 400);
            }
            
            $this->shipmentRepository->delete($id);
            
            return $this->successResponse(null, 'Shipment deleted successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to delete shipment', [
                'shipment_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to delete shipment', 500);
        }
    }

    /**
     * Get shipment tracking map data with Google Maps integration
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getTrackingMap(int $id): JsonResponse
    {
        try {
            $shipment = $this->shipmentRepository->findOrFail($id);
            $mapData = $this->mapTrackingService->getShipmentTrackingMap($shipment);
            
            return $this->successResponse($mapData, 'Tracking map data retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve tracking map data', [
                'shipment_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve tracking map data', 500);
        }
    }

    /**
     * Get public tracking map data by tracking number (no authentication required)
     * 
     * @param string $trackingNumber
     * @return JsonResponse
     */
    public function getPublicTrackingMap(string $trackingNumber): JsonResponse
    {
        try {
            $publicData = $this->mapTrackingService->getPublicTrackingData($trackingNumber);
            
            if (!$publicData) {
                return $this->errorResponse('Tracking number not found', 404);
            }
            
            // Get map data for public tracking
            $shipment = $this->shipmentRepository->findByTrackingNumber($trackingNumber);
            if ($shipment) {
                $mapData = $this->mapTrackingService->getShipmentTrackingMap($shipment);
                $publicData['map_data'] = $mapData;
            }
            
            return $this->successResponse($publicData, 'Public tracking data retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve public tracking map data', [
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve tracking data', 500);
        }
    }

    /**
     * Get live tracking data for admin dashboard (multiple shipments)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getLiveTrackingData(Request $request): JsonResponse
    {
        try {
            $shipmentIds = $request->get('shipment_ids', []);
            $liveData = $this->mapTrackingService->getLiveTrackingData($shipmentIds);
            
            return $this->successResponse($liveData, 'Live tracking data retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve live tracking data', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve live tracking data', 500);
        }
    }

    /**
     * Update shipment location with coordinates
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateLocation(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'location' => 'required|string|max:255',
            'coordinates' => 'nullable|array',
            'coordinates.lat' => 'required_with:coordinates|numeric|between:-90,90',
            'coordinates.lng' => 'required_with:coordinates|numeric|between:-180,180'
        ]);
        
        try {
            $shipment = $this->shipmentRepository->findOrFail($id);
            
            $coordinates = $request->has('coordinates') ? [
                'lat' => $request->input('coordinates.lat'),
                'lng' => $request->input('coordinates.lng')
            ] : null;
            
            $result = $this->mapTrackingService->updateShipmentLocation(
                $shipment,
                $request->location,
                $coordinates
            );
            
            return $this->successResponse($result, 'Shipment location updated successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to update shipment location', [
                'shipment_id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->only(['location', 'coordinates'])
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get route information between two locations
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getRoute(Request $request): JsonResponse
    {
        $request->validate([
            'origin' => 'required|array',
            'origin.lat' => 'required|numeric|between:-90,90',
            'origin.lng' => 'required|numeric|between:-180,180',
            'destination' => 'required|array',
            'destination.lat' => 'required|numeric|between:-90,90',
            'destination.lng' => 'required|numeric|between:-180,180'
        ]);
        
        try {
            $route = $this->mapTrackingService->getRoute(
                $request->origin,
                $request->destination
            );
            
            if (!$route) {
                return $this->errorResponse('Route not found', 404);
            }
            
            return $this->successResponse($route, 'Route information retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve route information', [
                'error' => $e->getMessage(),
                'origin' => $request->origin,
                'destination' => $request->destination
            ]);
            
            return $this->errorResponse('Failed to retrieve route information', 500);
        }
    }

    /**
     * Geocode a location to get coordinates
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function geocodeLocation(Request $request): JsonResponse
    {
        $request->validate([
            'location' => 'required|string|max:255'
        ]);
        
        try {
            $coordinates = $this->mapTrackingService->geocodeLocation($request->location);
            
            if (!$coordinates) {
                return $this->errorResponse('Location not found', 404);
            }
            
            return $this->successResponse($coordinates, 'Location geocoded successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to geocode location', [
                'location' => $request->location,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to geocode location', 500);
        }
    }

    /**
     * Public tracking endpoint (no authentication required)
     * 
     * @param string $trackingNumber
     * @return JsonResponse
     */
    public function publicTrack(string $trackingNumber): JsonResponse
    {
        try {
            $shipment = $this->shipmentRepository->findByTrackingNumber($trackingNumber);
            
            if (!$shipment) {
                return $this->errorResponse('Shipment not found', 404);
            }

            // Load relationships
            $shipment->load(['booking.customer', 'booking.vehicle', 'booking.route']);

            // Return comprehensive public-safe information
            $publicData = [
                'tracking_number' => $shipment->tracking_number,
                'status' => $shipment->status,
                'status_display' => ucfirst(str_replace('_', ' ', $shipment->status)),
                'progress' => $this->calculateProgressPercentage($shipment),
                
                // Timeline information
                'departure_date' => $shipment->departure_date,
                'estimated_delivery' => $shipment->estimated_arrival,
                'actual_delivery' => $shipment->actual_arrival,
                'last_updated' => $shipment->updated_at,
                
                // Location information
                'current_location' => $shipment->current_location ?? 'In transit',
                'origin' => $shipment->departure_port ?? $shipment->booking->route->origin_city ?? 'Origin',
                'destination' => $shipment->arrival_port ?? $shipment->booking->route->destination_city ?? 'Destination',
                'route' => ($shipment->departure_port ?? 'Origin') . ' → ' . ($shipment->arrival_port ?? 'Destination'),
                
                // Vehicle information
                'vehicle_info' => $this->getVehicleInfo($shipment->booking),
                'vin' => $shipment->booking->vehicle->vin ?? null,
                'make_model' => $this->getMakeModel($shipment->booking),
                'year' => $shipment->booking->vehicle->year ?? null,
                
                // Customer information (limited)
                'customer_name' => $shipment->booking->customer->full_name ?? $shipment->booking->customer->name ?? 'Customer',
                'customer_email' => $shipment->booking->customer->email ?? null,
                'customer_phone' => $shipment->booking->customer->phone ?? null,
                'booking_reference' => $shipment->booking->booking_reference ?? null,
                
                // Additional information
                'carrier_name' => $shipment->carrier_name,
                'notes' => $shipment->notes,
                'distance' => $this->calculateDistance($shipment),
            ];

            return $this->successResponse($publicData, 'Tracking information retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve public tracking data', [
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve tracking information', 500);
        }
    }

    /**
     * Get vehicle information string
     */
    private function getVehicleInfo($booking): string
    {
        if (!$booking || !$booking->vehicle) {
            return 'Vehicle information not available';
        }

        $vehicle = $booking->vehicle;
        return trim(sprintf(
            '%s %s %s',
            $vehicle->make ?? '',
            $vehicle->model ?? '',
            $vehicle->year ?? ''
        )) ?: 'Vehicle information';
    }

    /**
     * Get make and model string
     */
    private function getMakeModel($booking): ?string
    {
        if (!$booking || !$booking->vehicle) {
            return null;
        }

        $vehicle = $booking->vehicle;
        return trim(sprintf('%s %s', $vehicle->make ?? '', $vehicle->model ?? '')) ?: null;
    }

    /**
     * Calculate distance (placeholder - can be enhanced with actual calculation)
     */
    private function calculateDistance($shipment): ?string
    {
        // This can be enhanced with actual distance calculation
        // For now, return null or a placeholder
        return null;
    }

    /**
     * Get public tracking timeline data (no authentication required)
     * 
     * @param string $trackingNumber
     * @return JsonResponse
     */
    public function getPublicTrackingTimeline(string $trackingNumber): JsonResponse
    {
        try {
            $shipment = $this->shipmentRepository->findByTrackingNumber($trackingNumber);
            
            if (!$shipment) {
                return $this->errorResponse('Shipment not found', 404);
            }

            // Generate timeline events based on shipment status and history
            $events = $this->generateTimelineEvents($shipment);

            $timelineData = [
                'shipment_info' => [
                    'tracking_number' => $shipment->tracking_number,
                    'status' => $shipment->status,
                    'estimated_delivery' => $shipment->estimated_arrival_date
                ],
                'events' => $events,
                'summary' => [
                    'total_distance' => $shipment->total_distance ?? 'N/A',
                    'transit_time' => $this->calculateTransitTime($shipment),
                    'completed_checkpoints' => count(array_filter($events, fn($e) => $e['completed'])),
                    'total_checkpoints' => count($events),
                    'next_update' => $this->getNextUpdateTime($shipment)
                ]
            ];

            return $this->successResponse($timelineData, 'Timeline data retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve public tracking timeline', [
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve timeline data', 500);
        }
    }

    /**
     * Generate timeline events for a shipment
     * 
     * @param mixed $shipment
     * @return array
     */
    private function generateTimelineEvents($shipment): array
    {
        $events = [];
        $currentStatus = $shipment->status;

        // Define standard shipping timeline
        $standardEvents = [
            'booked' => [
                'title' => 'Booking Confirmed',
                'status' => 'booked',
                'description' => 'Your vehicle booking has been confirmed and is being prepared for shipment.',
                'location' => $shipment->departure_port ?? 'Origin'
            ],
            'picked_up' => [
                'title' => 'Vehicle Collected',
                'status' => 'picked_up',
                'description' => 'Vehicle has been collected and is ready for transport.',
                'location' => $shipment->departure_port ?? 'Origin'
            ],
            'in_transit' => [
                'title' => 'In Transit',
                'status' => 'in_transit',
                'description' => 'Your vehicle is currently being transported.',
                'location' => $shipment->current_location ?? 'En Route'
            ],
            'arrived' => [
                'title' => 'Arrived at Destination',
                'status' => 'arrived',
                'description' => 'Vehicle has arrived at the destination port.',
                'location' => $shipment->arrival_port ?? 'Destination'
            ],
            'delivered' => [
                'title' => 'Delivered',
                'status' => 'delivered',
                'description' => 'Vehicle has been successfully delivered.',
                'location' => $shipment->arrival_port ?? 'Destination'
            ]
        ];

        $statusOrder = ['booked', 'picked_up', 'in_transit', 'arrived', 'delivered'];
        $currentIndex = array_search($currentStatus, $statusOrder);

        foreach ($statusOrder as $index => $status) {
            $event = $standardEvents[$status];
            $event['completed'] = $index <= $currentIndex;
            $event['current'] = $index === $currentIndex;
            $event['timestamp'] = $index <= $currentIndex ? 
                ($shipment->updated_at ?? now()) : null;

            $events[] = $event;
        }

        return $events;
    }

    /**
     * Calculate progress percentage for shipment
     * 
     * @param mixed $shipment
     * @return int
     */
    private function calculateProgressPercentage($shipment): int
    {
        $statusProgress = [
            'pending' => 5,
            'preparing' => 15,
            'confirmed' => 20,
            'in_transit' => 60,
            'customs' => 80,
            'delivered' => 100,
            'delayed' => 50,
            'cancelled' => 0
        ];

        return $statusProgress[$shipment->status] ?? 0;
    }

    /**
     * Calculate transit time for shipment
     * 
     * @param mixed $shipment
     * @return string
     */
    private function calculateTransitTime($shipment): string
    {
        if ($shipment->created_at && $shipment->status === 'delivered') {
            $days = $shipment->created_at->diffInDays($shipment->updated_at);
            return $days . ' days';
        }

        return 'In progress';
    }

    /**
     * Get next update time for shipment
     * 
     * @param mixed $shipment
     * @return string
     */
    private function getNextUpdateTime($shipment): string
    {
        if ($shipment->status === 'delivered') {
            return 'Completed';
        }

        return 'Within 24 hours';
    }
}
