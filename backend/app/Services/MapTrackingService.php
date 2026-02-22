<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Map Tracking Service
 * 
 * Handles Google Maps integration for real-time vehicle tracking
 * with route visualization and location geocoding
 */
class MapTrackingService
{
    private const GOOGLE_MAPS_API_KEY = 'GOOGLE_MAPS_API_KEY'; // Will be set in .env
    private const GEOCODING_API = 'https://maps.googleapis.com/maps/api/geocode/json';
    private const DIRECTIONS_API = 'https://maps.googleapis.com/maps/api/directions/json';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get tracking map data for a shipment
     */
    public function getShipmentTrackingMap(Shipment $shipment): array
    {
        try {
            $cacheKey = "shipment.map.{$shipment->id}";
            
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($shipment) {
                // Get origin and destination coordinates
                $originCoords = $this->geocodeLocation($shipment->departure_port);
                $destinationCoords = $this->geocodeLocation($shipment->arrival_port);
                $currentCoords = $this->geocodeLocation($shipment->current_location);
                
                // Get route information
                $route = null;
                if ($originCoords && $destinationCoords) {
                    $route = $this->getRoute($originCoords, $destinationCoords);
                }
                
                // Calculate progress percentage
                $progressPercentage = $this->calculateRouteProgress($shipment, $route);
                
                return [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'status' => $shipment->status,
                    'locations' => [
                        'origin' => [
                            'name' => $shipment->departure_port,
                            'coordinates' => $originCoords,
                            'type' => 'departure',
                        ],
                        'destination' => [
                            'name' => $shipment->arrival_port,
                            'coordinates' => $destinationCoords,
                            'type' => 'arrival',
                        ],
                        'current' => [
                            'name' => $shipment->current_location,
                            'coordinates' => $currentCoords,
                            'type' => 'current',
                            'updated_at' => $shipment->updated_at->toISOString(),
                        ],
                    ],
                    'route' => $route,
                    'progress' => [
                        'percentage' => $progressPercentage,
                        'status_label' => $this->getStatusLabel($shipment->status),
                        'estimated_arrival' => $shipment->estimated_arrival?->toISOString(),
                        'actual_arrival' => $shipment->actual_arrival?->toISOString(),
                    ],
                    'tracking_history' => $this->getTrackingHistory($shipment),
                    'map_config' => $this->getMapConfiguration($originCoords, $destinationCoords, $currentCoords),
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get shipment tracking map', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Get live tracking data for multiple shipments (admin view)
     */
    public function getLiveTrackingData(array $shipmentIds = []): array
    {
        try {
            $query = Shipment::with(['booking.customer', 'booking.vehicle']);
            
            if (!empty($shipmentIds)) {
                $query->whereIn('id', $shipmentIds);
            } else {
                // Get active shipments only
                $query->whereIn('status', ['preparing', 'in_transit', 'customs']);
            }
            
            $shipments = $query->get();
            $trackingData = [];
            
            foreach ($shipments as $shipment) {
                $currentCoords = $this->geocodeLocation($shipment->current_location);
                
                if ($currentCoords) {
                    $trackingData[] = [
                        'shipment_id' => $shipment->id,
                        'tracking_number' => $shipment->tracking_number,
                        'customer_name' => $shipment->booking->customer->full_name ?? 'Unknown',
                        'vehicle_info' => $this->getVehicleInfo($shipment->booking),
                        'current_location' => [
                            'name' => $shipment->current_location,
                            'coordinates' => $currentCoords,
                            'updated_at' => $shipment->updated_at->toISOString(),
                        ],
                        'status' => $shipment->status,
                        'status_label' => $this->getStatusLabel($shipment->status),
                        'carrier' => $shipment->carrier_name,
                        'vessel' => $shipment->vessel_name,
                        'estimated_arrival' => $shipment->estimated_arrival?->toISOString(),
                    ];
                }
            }
            
            return [
                'shipments' => $trackingData,
                'total_count' => count($trackingData),
                'map_bounds' => $this->calculateMapBounds($trackingData),
                'last_updated' => now()->toISOString(),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get live tracking data', [
                'shipment_ids' => $shipmentIds,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Update shipment location with coordinates
     */
    public function updateShipmentLocation(Shipment $shipment, string $newLocation, ?array $coordinates = null): array
    {
        try {
            $previousLocation = $shipment->current_location;
            
            // Update location
            $shipment->current_location = $newLocation;
            
            // Geocode if coordinates not provided
            if (!$coordinates) {
                $coordinates = $this->geocodeLocation($newLocation);
            }
            
            // Add to tracking updates
            $trackingUpdates = $shipment->tracking_updates ?? [];
            $trackingUpdates[] = [
                'location' => $newLocation,
                'coordinates' => $coordinates,
                'timestamp' => now()->toISOString(),
                'updated_by' => auth()->user()?->name ?? 'System',
            ];
            
            $shipment->tracking_updates = $trackingUpdates;
            $shipment->save();
            
            // Clear cache
            Cache::forget("shipment.map.{$shipment->id}");
            Cache::forget("location.geocode." . md5($newLocation));
            
            // Broadcast real-time update
            $realTimeService = app(\App\Services\RealTimeService::class);
            $realTimeService->broadcastShipmentLocationUpdate(
                $shipment, 
                $previousLocation, 
                "Location updated to: {$newLocation}",
                auth()->user()
            );
            
            return [
                'success' => true,
                'previous_location' => $previousLocation,
                'new_location' => $newLocation,
                'coordinates' => $coordinates,
                'tracking_updates_count' => count($trackingUpdates),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to update shipment location', [
                'shipment_id' => $shipment->id,
                'new_location' => $newLocation,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Get route between two points
     */
    public function getRoute(array $origin, array $destination): ?array
    {
        try {
            $cacheKey = 'route.' . md5(json_encode($origin) . json_encode($destination));
            
            return Cache::remember($cacheKey, self::CACHE_TTL * 24, function () use ($origin, $destination) {
                $response = Http::get(self::DIRECTIONS_API, [
                    'origin' => "{$origin['lat']},{$origin['lng']}",
                    'destination' => "{$destination['lat']},{$destination['lng']}",
                    'key' => env(self::GOOGLE_MAPS_API_KEY),
                    'mode' => 'driving', // Can be changed based on shipping method
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    if ($data['status'] === 'OK' && !empty($data['routes'])) {
                        $route = $data['routes'][0];
                        
                        return [
                            'polyline' => $route['overview_polyline']['points'],
                            'distance' => $route['legs'][0]['distance']['text'],
                            'duration' => $route['legs'][0]['duration']['text'],
                            'steps' => $this->simplifyRouteSteps($route['legs'][0]['steps']),
                        ];
                    }
                }
                
                return null;
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get route', [
                'origin' => $origin,
                'destination' => $destination,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Geocode a location to get coordinates
     */
    public function geocodeLocation(string $location): ?array
    {
        if (empty($location)) {
            return null;
        }
        
        try {
            $cacheKey = 'location.geocode.' . md5($location);
            
            return Cache::remember($cacheKey, self::CACHE_TTL * 24, function () use ($location) {
                $response = Http::get(self::GEOCODING_API, [
                    'address' => $location,
                    'key' => env(self::GOOGLE_MAPS_API_KEY),
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    if ($data['status'] === 'OK' && !empty($data['results'])) {
                        $result = $data['results'][0];
                        
                        return [
                            'lat' => $result['geometry']['location']['lat'],
                            'lng' => $result['geometry']['location']['lng'],
                            'formatted_address' => $result['formatted_address'],
                            'place_id' => $result['place_id'],
                        ];
                    }
                }
                
                // Return fallback coordinates for common ports
                return $this->getFallbackCoordinates($location);
            });
            
        } catch (Exception $e) {
            Log::error('Failed to geocode location', [
                'location' => $location,
                'error' => $e->getMessage(),
            ]);
            
            return $this->getFallbackCoordinates($location);
        }
    }

    /**
     * Get public tracking data (no authentication required)
     */
    public function getPublicTrackingData(string $trackingNumber): ?array
    {
        try {
            $shipment = Shipment::where('tracking_number', $trackingNumber)
                ->with(['booking.customer', 'booking.vehicle'])
                ->first();
            
            if (!$shipment) {
                return null;
            }
            
            return [
                'tracking_number' => $trackingNumber,
                'status' => $shipment->status,
                'status_label' => $this->getStatusLabel($shipment->status),
                'current_location' => $shipment->current_location,
                'current_coordinates' => $this->geocodeLocation($shipment->current_location),
                'departure_port' => $shipment->departure_port,
                'arrival_port' => $shipment->arrival_port,
                'estimated_arrival' => $shipment->estimated_arrival?->toDateString(),
                'carrier_name' => $shipment->carrier_name,
                'vessel_name' => $shipment->vessel_name,
                'vehicle_info' => $this->getVehicleInfo($shipment->booking, false), // Limited info for public
                'tracking_history' => $this->getPublicTrackingHistory($shipment),
                'last_updated' => $shipment->updated_at->toISOString(),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get public tracking data', [
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    // Private helper methods

    private function calculateRouteProgress(Shipment $shipment, ?array $route): float
    {
        // Simple progress calculation based on status
        $statusProgress = [
            'preparing' => 10,
            'in_transit' => 50,
            'customs' => 80,
            'delivered' => 100,
            'delayed' => 50, // Assume halfway if delayed
        ];
        
        return $statusProgress[$shipment->status] ?? 0;
    }

    private function getStatusLabel(string $status): string
    {
        $labels = [
            'preparing' => 'Preparing for Shipment',
            'in_transit' => 'In Transit',
            'customs' => 'Customs Clearance',
            'delivered' => 'Delivered',
            'delayed' => 'Delayed',
        ];
        
        return $labels[$status] ?? ucfirst($status);
    }

    private function getTrackingHistory(Shipment $shipment): array
    {
        $history = $shipment->tracking_updates ?? [];
        
        // Add initial departure
        array_unshift($history, [
            'location' => $shipment->departure_port,
            'coordinates' => $this->geocodeLocation($shipment->departure_port),
            'timestamp' => $shipment->departure_date?->toISOString() ?? $shipment->created_at->toISOString(),
            'updated_by' => 'System',
            'type' => 'departure',
        ]);
        
        return array_reverse($history); // Most recent first
    }

    private function getPublicTrackingHistory(Shipment $shipment): array
    {
        $history = $this->getTrackingHistory($shipment);
        
        // Remove sensitive information for public view
        return array_map(function ($item) {
            return [
                'location' => $item['location'],
                'timestamp' => $item['timestamp'],
                'type' => $item['type'] ?? 'update',
            ];
        }, $history);
    }

    private function getVehicleInfo(Booking $booking, bool $fullInfo = true): array
    {
        if (!$booking->vehicle) {
            return ['info' => 'Vehicle information not available'];
        }
        
        $vehicle = $booking->vehicle;
        $basicInfo = [
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
        ];
        
        if ($fullInfo) {
            $basicInfo['color'] = $vehicle->color;
            $basicInfo['engine_type'] = $vehicle->engine_type;
            $basicInfo['customer'] = $booking->customer->full_name ?? 'Unknown';
        }
        
        return $basicInfo;
    }

    private function getMapConfiguration(array $origin = null, array $destination = null, array $current = null): array
    {
        $bounds = [];
        $center = ['lat' => 0, 'lng' => 0];
        
        if ($origin) $bounds[] = $origin;
        if ($destination) $bounds[] = $destination;
        if ($current) $bounds[] = $current;
        
        if (!empty($bounds)) {
            $center = $this->calculateCenter($bounds);
        }
        
        return [
            'center' => $center,
            'zoom' => $this->calculateZoom($bounds),
            'bounds' => $bounds,
            'map_type' => 'roadmap',
        ];
    }

    private function calculateCenter(array $coordinates): array
    {
        if (empty($coordinates)) {
            return ['lat' => 0, 'lng' => 0];
        }
        
        $lat = array_sum(array_column($coordinates, 'lat')) / count($coordinates);
        $lng = array_sum(array_column($coordinates, 'lng')) / count($coordinates);
        
        return ['lat' => $lat, 'lng' => $lng];
    }

    private function calculateZoom(array $bounds): int
    {
        if (count($bounds) < 2) {
            return 10;
        }
        
        // Simple zoom calculation based on distance
        $latDiff = max(array_column($bounds, 'lat')) - min(array_column($bounds, 'lat'));
        $lngDiff = max(array_column($bounds, 'lng')) - min(array_column($bounds, 'lng'));
        $maxDiff = max($latDiff, $lngDiff);
        
        if ($maxDiff > 50) return 3;
        if ($maxDiff > 20) return 4;
        if ($maxDiff > 10) return 5;
        if ($maxDiff > 5) return 6;
        if ($maxDiff > 2) return 7;
        if ($maxDiff > 1) return 8;
        return 10;
    }

    private function calculateMapBounds(array $trackingData): array
    {
        $coordinates = [];
        
        foreach ($trackingData as $data) {
            if (isset($data['current_location']['coordinates'])) {
                $coordinates[] = $data['current_location']['coordinates'];
            }
        }
        
        if (empty($coordinates)) {
            return ['center' => ['lat' => 0, 'lng' => 0], 'zoom' => 2];
        }
        
        return [
            'center' => $this->calculateCenter($coordinates),
            'zoom' => $this->calculateZoom($coordinates),
            'bounds' => $coordinates,
        ];
    }

    private function simplifyRouteSteps(array $steps): array
    {
        return array_map(function ($step) {
            return [
                'instruction' => strip_tags($step['html_instructions']),
                'distance' => $step['distance']['text'],
                'duration' => $step['duration']['text'],
            ];
        }, array_slice($steps, 0, 10)); // Limit to first 10 steps
    }

    private function getFallbackCoordinates(string $location): ?array
    {
        // Fallback coordinates for common shipping ports
        $fallbackCoords = [
            'Port of Tokyo' => ['lat' => 35.6762, 'lng' => 139.6503],
            'Port of Yokohama' => ['lat' => 35.4437, 'lng' => 139.6380],
            'Port of Mombasa' => ['lat' => -4.0435, 'lng' => 39.6682],
            'Port of Dar es Salaam' => ['lat' => -6.8160, 'lng' => 39.2803],
            'Kampala' => ['lat' => 0.3476, 'lng' => 32.5825],
            'Dubai' => ['lat' => 25.2048, 'lng' => 55.2708],
            'Port of Dubai' => ['lat' => 25.2866, 'lng' => 55.3573],
            'London' => ['lat' => 51.5074, 'lng' => -0.1278],
            'Port of London' => ['lat' => 51.5074, 'lng' => -0.1278],
        ];
        
        foreach ($fallbackCoords as $place => $coords) {
            if (stripos($location, $place) !== false || stripos($place, $location) !== false) {
                return $coords;
            }
        }
        
        return null;
    }
}