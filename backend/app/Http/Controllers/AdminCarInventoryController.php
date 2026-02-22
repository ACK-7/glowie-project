<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarBrand;
use App\Models\CarCategory;
use App\Models\CarImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminCarInventoryController extends BaseApiController
{
    // Car Management
    public function getCars(Request $request): JsonResponse
    {
        try {
            $query = Car::with(['brand', 'category', 'images']);

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('model', 'like', "%{$search}%")
                      ->orWhereHas('brand', function ($brandQuery) use ($search) {
                          $brandQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 50);
            $cars = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->successResponse($cars, 'Cars retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve cars', 500, ['error' => $e->getMessage()]);
        }
    }

    public function storeCar(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'brand_id' => 'required|exists:car_brands,id',
                'category_id' => 'required|exists:car_categories,id',
                'model' => 'required|string|max:255',
                'year' => 'required|integer|min:1990|max:2030',
                'price' => 'required|numeric|min:0',
                'color' => 'required|string|max:100',
                'fuel_type' => 'required|in:petrol,diesel,hybrid,electric',
                'transmission' => 'required|in:automatic,manual,cvt',
                'condition' => 'required|in:new,used,certified_pre_owned',
                'location_country' => 'required|string|max:100',
                'location_city' => 'required|string|max:100',
                'description' => 'nullable|string',
                'engine_type' => 'nullable|string|max:100',
                'mileage' => 'nullable|integer|min:0',
                'drive_type' => 'nullable|in:fwd,rwd,awd,4wd',
                'doors' => 'nullable|integer|min:2|max:6',
                'seats' => 'nullable|integer|min:1|max:12',
                'is_featured' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            $car = Car::create($request->all());

            return $this->successResponse($car->load(['brand', 'category']), 'Car created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create car', 500, ['error' => $e->getMessage()]);
        }
    }

    public function updateCar(Request $request, $id): JsonResponse
    {
        try {
            $car = Car::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'brand_id' => 'sometimes|exists:car_brands,id',
                'category_id' => 'sometimes|exists:car_categories,id',
                'model' => 'sometimes|string|max:255',
                'year' => 'sometimes|integer|min:1990|max:2030',
                'price' => 'sometimes|numeric|min:0',
                'color' => 'sometimes|string|max:100',
                'fuel_type' => 'sometimes|in:petrol,diesel,hybrid,electric',
                'transmission' => 'sometimes|in:automatic,manual,cvt',
                'condition' => 'sometimes|in:new,used,certified_pre_owned',
                'location_country' => 'sometimes|string|max:100',
                'location_city' => 'sometimes|string|max:100',
                'description' => 'nullable|string',
                'engine_type' => 'nullable|string|max:100',
                'mileage' => 'nullable|integer|min:0',
                'drive_type' => 'nullable|in:fwd,rwd,awd,4wd',
                'doors' => 'nullable|integer|min:2|max:6',
                'seats' => 'nullable|integer|min:1|max:12',
                'is_featured' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            $car->update($request->all());

            return $this->successResponse($car->load(['brand', 'category']), 'Car updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update car', 500, ['error' => $e->getMessage()]);
        }
    }

    public function deleteCar($id): JsonResponse
    {
        try {
            $car = Car::findOrFail($id);
            
            // Delete associated images
            foreach ($car->images as $image) {
                if ($image->image_url && Storage::exists($image->image_url)) {
                    Storage::delete($image->image_url);
                }
                $image->delete();
            }
            
            $car->delete();

            return $this->successResponse(null, 'Car deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete car', 500, ['error' => $e->getMessage()]);
        }
    }

    // Brand Management
    public function getBrands(Request $request): JsonResponse
    {
        try {
            $query = CarBrand::withCount('activeCars');

            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $brands = $query->orderBy('name')->get();

            return $this->successResponse($brands, 'Brands retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve brands', 500, ['error' => $e->getMessage()]);
        }
    }

    public function storeBrand(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:car_brands,name',
                'country_of_origin' => 'required|string|max:100',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            $brand = CarBrand::create($request->all());

            return $this->successResponse($brand, 'Brand created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create brand', 500, ['error' => $e->getMessage()]);
        }
    }

    public function updateBrand(Request $request, $id): JsonResponse
    {
        try {
            $brand = CarBrand::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:car_brands,name,' . $id,
                'country_of_origin' => 'sometimes|string|max:100',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            $brand->update($request->all());

            return $this->successResponse($brand, 'Brand updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update brand', 500, ['error' => $e->getMessage()]);
        }
    }

    public function deleteBrand($id): JsonResponse
    {
        try {
            $brand = CarBrand::findOrFail($id);
            
            // Check if brand has cars
            if ($brand->cars()->count() > 0) {
                return $this->errorResponse('Cannot delete brand with associated cars', 400);
            }
            
            $brand->delete();

            return $this->successResponse(null, 'Brand deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete brand', 500, ['error' => $e->getMessage()]);
        }
    }

    // Category Management
    public function getCategories(Request $request): JsonResponse
    {
        try {
            $query = CarCategory::withCount('activeCars');

            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $categories = $query->orderBy('name')->get();

            return $this->successResponse($categories, 'Categories retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve categories', 500, ['error' => $e->getMessage()]);
        }
    }

    public function storeCategory(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:car_categories,name',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            $category = CarCategory::create($request->all());

            return $this->successResponse($category, 'Category created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create category', 500, ['error' => $e->getMessage()]);
        }
    }

    public function updateCategory(Request $request, $id): JsonResponse
    {
        try {
            $category = CarCategory::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:car_categories,name,' . $id,
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            $category->update($request->all());

            return $this->successResponse($category, 'Category updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update category', 500, ['error' => $e->getMessage()]);
        }
    }

    public function deleteCategory($id): JsonResponse
    {
        try {
            $category = CarCategory::findOrFail($id);
            
            // Check if category has cars
            if ($category->cars()->count() > 0) {
                return $this->errorResponse('Cannot delete category with associated cars', 400);
            }
            
            $category->delete();

            return $this->successResponse(null, 'Category deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete category', 500, ['error' => $e->getMessage()]);
        }
    }

    // Image Management
    public function uploadCarImage(Request $request, $carId): JsonResponse
    {
        try {
            $car = Car::findOrFail($carId);

            \Illuminate\Support\Facades\Log::info('Image upload attempt', [
                'car_id' => $carId,
                'has_file' => $request->hasFile('image'),
                'file_info' => $request->hasFile('image') ? [
                    'original_name' => $request->file('image')->getClientOriginalName(),
                    'mime_type' => $request->file('image')->getMimeType(),
                    'size' => $request->file('image')->getSize(),
                ] : null,
                'all_data' => $request->all()
            ]);

            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'alt_text' => 'nullable|string|max:255',
                'is_primary' => 'nullable'
            ]);

            if ($validator->fails()) {
                \Illuminate\Support\Facades\Log::warning('Image validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            $image = $request->file('image');
            $path = $image->store('car-images', 'public');

            // If this is set as primary, unset other primary images
            if ($request->get('is_primary', false)) {
                CarImage::where('car_id', $carId)->update(['is_primary' => false]);
            }

            $carImage = CarImage::create([
                'car_id' => $carId,
                'image_url' => Storage::url($path),
                'alt_text' => $request->get('alt_text', ''),
                'is_primary' => $request->get('is_primary', false),
                'type' => 'exterior'
            ]);

            return $this->successResponse($carImage, 'Image uploaded successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload image', 500, ['error' => $e->getMessage()]);
        }
    }

    public function deleteCarImage($imageId): JsonResponse
    {
        try {
            $image = CarImage::findOrFail($imageId);
            
            // Delete file from storage
            if ($image->image_url && Storage::exists($image->image_url)) {
                Storage::delete($image->image_url);
            }
            
            $image->delete();

            return $this->successResponse(null, 'Image deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete image', 500, ['error' => $e->getMessage()]);
        }
    }
}