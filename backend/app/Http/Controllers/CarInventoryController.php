<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarBrand;
use App\Models\CarCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CarInventoryController extends BaseApiController
{
    /**
     * Get all available cars with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Car::with(['brand', 'category', 'images'])
                        ->available();

            // Apply filters
            if ($request->has('brand')) {
                $query->byBrand($request->brand);
            }

            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            if ($request->has('condition')) {
                $query->byCondition($request->condition);
            }

            if ($request->has('price_min') && $request->has('price_max')) {
                $query->byPriceRange($request->price_min, $request->price_max);
            } elseif ($request->has('price_min')) {
                $query->where('price', '>=', $request->price_min);
            } elseif ($request->has('price_max')) {
                $query->where('price', '<=', $request->price_max);
            }

            if ($request->has('year_min') && $request->has('year_max')) {
                $query->byYearRange($request->year_min, $request->year_max);
            } elseif ($request->has('year_min')) {
                $query->where('year', '>=', $request->year_min);
            } elseif ($request->has('year_max')) {
                $query->where('year', '<=', $request->year_max);
            }

            if ($request->has('location')) {
                $query->byLocation($request->location);
            }

            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Sorting
            $sortBy = $request->get('sort', 'created_at');
            
            switch ($sortBy) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'year_desc':
                    $query->orderBy('year', 'desc');
                    break;
                case 'year_asc':
                    $query->orderBy('year', 'asc');
                    break;
                case 'name':
                    $query->orderBy('model', 'asc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            // Featured cars first if requested
            if ($request->get('featured_first', false)) {
                $query->orderBy('is_featured', 'desc');
            }

            $perPage = min($request->get('per_page', 12), 50); // Max 50 per page
            $cars = $query->paginate($perPage);

            return $this->successResponse($cars, 'Cars retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve cars', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get featured cars for homepage
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $limit = min($request->get('limit', 6), 12); // Max 12 featured cars
            
            $cars = Car::with(['brand', 'category', 'images'])
                      ->available()
                      ->featured()
                      ->orderBy('created_at', 'desc')
                      ->limit($limit)
                      ->get();

            return $this->successResponse($cars, 'Featured cars retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve featured cars', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get a specific car by slug
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $car = Car::with(['brand', 'category', 'images'])
                     ->where('slug', $slug)
                     ->available()
                     ->firstOrFail();

            // Increment view count
            $car->incrementViews();

            return $this->successResponse($car, 'Car details retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Car not found', 404);
        }
    }

    /**
     * Get all active brands with car counts and pagination
     */
    public function brands(Request $request): JsonResponse
    {
        try {
            $query = CarBrand::active()->ordered();

            // For home page, show all brands. For brands page, filter by car count
            if (!$request->has('page') && !$request->has('per_page') && !$request->has('search')) {
                // Home page - show all active brands
                $query->withCount(['activeCars']);
            } else {
                // Brands page - only show brands with cars
                $query->withCount(['activeCars'])->having('active_cars_count', '>', 0);
            }

            // Search functionality
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Sorting
            $sortBy = $request->get('sort', 'name');
            $allowedSorts = ['name', 'cars_count', 'created_at'];
            
            if ($sortBy === 'cars_count') {
                $query->orderBy('active_cars_count', 'desc');
            } elseif (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, 'asc');
            }

            // Check if pagination is requested
            if ($request->has('page') || $request->has('per_page')) {
                $perPage = min($request->get('per_page', 12), 50); // Max 50 per page
                $brands = $query->paginate($perPage);
            } else {
                // Return all brands for home page
                $brands = $query->get();
            }

            return $this->successResponse($brands, 'Brands retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve brands', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get all active categories with car counts
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = CarCategory::active()
                                   ->ordered()
                                   ->withCount(['activeCars'])
                                   ->having('active_cars_count', '>', 0)
                                   ->get();

            return $this->successResponse($categories, 'Categories retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve categories', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get car statistics for filters
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_cars' => Car::available()->count(),
                'price_range' => [
                    'min' => Car::available()->min('price'),
                    'max' => Car::available()->max('price'),
                ],
                'year_range' => [
                    'min' => Car::available()->min('year'),
                    'max' => Car::available()->max('year'),
                ],
                'locations' => Car::available()
                                 ->select('location_country')
                                 ->groupBy('location_country')
                                 ->pluck('location_country'),
                'fuel_types' => Car::available()
                                  ->select('fuel_type')
                                  ->whereNotNull('fuel_type')
                                  ->groupBy('fuel_type')
                                  ->pluck('fuel_type'),
                'transmissions' => Car::available()
                                     ->select('transmission')
                                     ->whereNotNull('transmission')
                                     ->groupBy('transmission')
                                     ->pluck('transmission'),
            ];

            return $this->successResponse($stats, 'Car statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Search cars with suggestions
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            
            if (strlen($query) < 2) {
                return $this->errorResponse('Search query must be at least 2 characters', 400);
            }

            $cars = Car::with(['brand', 'category', 'images'])
                      ->available()
                      ->search($query)
                      ->orderBy('views_count', 'desc')
                      ->limit(20)
                      ->get();

            // Also get brand and model suggestions
            $brandSuggestions = CarBrand::where('name', 'like', "%{$query}%")
                                       ->active()
                                       ->limit(5)
                                       ->pluck('name');

            $modelSuggestions = Car::where('model', 'like', "%{$query}%")
                                  ->available()
                                  ->distinct()
                                  ->limit(5)
                                  ->pluck('model');

            $response = [
                'cars' => $cars,
                'suggestions' => [
                    'brands' => $brandSuggestions,
                    'models' => $modelSuggestions,
                ]
            ];

            return $this->successResponse($response, 'Search results retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Search failed', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Record car inquiry (for analytics)
     */
    public function inquiry(Request $request, string $slug): JsonResponse
    {
        try {
            $car = Car::where('slug', $slug)->available()->firstOrFail();
            $car->incrementInquiries();

            return $this->successResponse(null, 'Inquiry recorded successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Car not found', 404);
        }
    }

    /**
     * Get similar cars based on brand, category, and price range
     */
    public function similar(string $slug): JsonResponse
    {
        try {
            $car = Car::where('slug', $slug)->available()->firstOrFail();
            
            $priceRange = $car->price * 0.3; // 30% price range
            
            $similarCars = Car::with(['brand', 'category', 'images'])
                             ->available()
                             ->where('id', '!=', $car->id)
                             ->where(function ($query) use ($car, $priceRange) {
                                 $query->where('brand_id', $car->brand_id)
                                       ->orWhere('category_id', $car->category_id)
                                       ->orWhereBetween('price', [
                                           $car->price - $priceRange,
                                           $car->price + $priceRange
                                       ]);
                             })
                             ->orderBy('brand_id', $car->brand_id ? 'desc' : 'asc')
                             ->orderBy('category_id', $car->category_id ? 'desc' : 'asc')
                             ->limit(6)
                             ->get();

            return $this->successResponse($similarCars, 'Similar cars retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Car not found', 404);
        }
    }
}