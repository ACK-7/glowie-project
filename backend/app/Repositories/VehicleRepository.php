<?php

namespace App\Repositories;

use App\Models\Vehicle;
use App\Repositories\Contracts\VehicleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class VehicleRepository extends BaseRepository implements VehicleRepositoryInterface
{
    public function __construct(Vehicle $model)
    {
        parent::__construct($model);
    }

    /**
     * Find vehicle with relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?Vehicle
    {
        $defaultRelations = ['bookings'];
        $relations = empty($relations) ? $defaultRelations : $relations;
        
        return $this->with($relations)->find($id);
    }

    /**
     * Get vehicles by make
     */
    public function getByMake(string $make): Collection
    {
        return $this->query->byMake($make)->get();
    }

    /**
     * Get vehicles by model
     */
    public function getByModel(string $model): Collection
    {
        return $this->query->byModel($model)->get();
    }

    /**
     * Get vehicles by year
     */
    public function getByYear(int $year): Collection
    {
        return $this->query->byYear($year)->get();
    }

    /**
     * Get vehicles by year range
     */
    public function getByYearRange(int $startYear, int $endYear): Collection
    {
        return $this->query->byYearRange($startYear, $endYear)->get();
    }

    /**
     * Get running vehicles
     */
    public function getRunning(): Collection
    {
        return $this->query->running()->get();
    }

    /**
     * Get non-running vehicles
     */
    public function getNotRunning(): Collection
    {
        return $this->query->notRunning()->get();
    }

    /**
     * Get vehicles by category
     */
    public function getByCategory(string $category): Collection
    {
        $vehicles = $this->all();
        
        return $vehicles->filter(function ($vehicle) use ($category) {
            return $vehicle->getCategory() === $category;
        });
    }

    /**
     * Get vehicles by shipping class
     */
    public function getByShippingClass(string $class): Collection
    {
        $vehicles = $this->all();
        
        return $vehicles->filter(function ($vehicle) use ($class) {
            return $vehicle->getShippingClass() === $class;
        });
    }

    /**
     * Search vehicles
     */
    public function searchVehicles(string $query): Collection
    {
        return $this->search($query, ['make', 'model', 'vin', 'license_plate']);
    }

    /**
     * Get vehicle statistics
     */
    public function getStatistics(): array
    {
        $total = $this->count();
        $running = $this->query->running()->count();
        $notRunning = $this->query->notRunning()->count();

        $this->resetQuery();

        // Get category distribution
        $vehicles = $this->all();
        $categoryDistribution = [
            'modern' => 0,
            'classic' => 0,
            'vintage' => 0,
        ];

        $shippingClassDistribution = [
            'standard' => 0,
            'medium' => 0,
            'large' => 0,
            'oversized' => 0,
        ];

        foreach ($vehicles as $vehicle) {
            $category = $vehicle->getCategory();
            $shippingClass = $vehicle->getShippingClass();
            
            $categoryDistribution[$category]++;
            $shippingClassDistribution[$shippingClass]++;
        }

        // Get make distribution
        $makeDistribution = $this->query
            ->selectRaw('make, COUNT(*) as count')
            ->groupBy('make')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'make')
            ->toArray();

        $this->resetQuery();

        return [
            'total' => $total,
            'running' => $running,
            'not_running' => $notRunning,
            'running_rate' => $total > 0 ? ($running / $total) * 100 : 0,
            'category_distribution' => $categoryDistribution,
            'shipping_class_distribution' => $shippingClassDistribution,
            'make_distribution' => $makeDistribution,
        ];
    }

    /**
     * Get popular makes
     */
    public function getPopularMakes(int $limit = 10): array
    {
        return $this->query
            ->selectRaw('make, COUNT(*) as count')
            ->groupBy('make')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('count', 'make')
            ->toArray();
    }

    /**
     * Get popular models
     */
    public function getPopularModels(int $limit = 10): array
    {
        return $this->query
            ->selectRaw('CONCAT(make, " ", model) as full_model, COUNT(*) as count')
            ->groupBy('make', 'model')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('count', 'full_model')
            ->toArray();
    }

    /**
     * Get recent vehicles
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->orderBy('created_at', 'desc')
                   ->query->limit($limit)->get();
    }

    /**
     * Get vehicles requiring attention
     */
    public function getRequiringAttention(): Collection
    {
        return $this->query->where(function ($query) {
            $query->whereNull('vin')
                  ->orWhere('is_running', false)
                  ->orWhereNull('weight')
                  ->orWhereNull('length')
                  ->orWhereNull('width')
                  ->orWhereNull('height');
        })->get();
    }

    /**
     * Apply search filter for vehicles
     */
    protected function applySearchFilter(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('make', 'LIKE', "%{$search}%")
              ->orWhere('model', 'LIKE', "%{$search}%")
              ->orWhere('vin', 'LIKE', "%{$search}%")
              ->orWhere('license_plate', 'LIKE', "%{$search}%")
              ->orWhere('color', 'LIKE', "%{$search}%")
              ->orWhere('engine_type', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Apply additional filters specific to vehicles
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query = parent::applyFilters($query, $filters);

        // Vehicle-specific filters
        if (isset($filters['make']) && !empty($filters['make'])) {
            $query->where('make', 'LIKE', "%{$filters['make']}%");
        }

        if (isset($filters['model']) && !empty($filters['model'])) {
            $query->where('model', 'LIKE', "%{$filters['model']}%");
        }

        if (isset($filters['year']) && !empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        if (isset($filters['year_from']) && !empty($filters['year_from'])) {
            $query->where('year', '>=', $filters['year_from']);
        }

        if (isset($filters['year_to']) && !empty($filters['year_to'])) {
            $query->where('year', '<=', $filters['year_to']);
        }

        if (isset($filters['color']) && !empty($filters['color'])) {
            $query->where('color', 'LIKE', "%{$filters['color']}%");
        }

        if (isset($filters['is_running']) && $filters['is_running'] !== '') {
            $query->where('is_running', (bool)$filters['is_running']);
        }

        if (isset($filters['transmission']) && !empty($filters['transmission'])) {
            $query->where('transmission', $filters['transmission']);
        }

        if (isset($filters['engine_type']) && !empty($filters['engine_type'])) {
            $query->where('engine_type', 'LIKE', "%{$filters['engine_type']}%");
        }

        if (isset($filters['weight_min']) && !empty($filters['weight_min'])) {
            $query->where('weight', '>=', $filters['weight_min']);
        }

        if (isset($filters['weight_max']) && !empty($filters['weight_max'])) {
            $query->where('weight', '<=', $filters['weight_max']);
        }

        if (isset($filters['category']) && !empty($filters['category'])) {
            // This is complex as category is calculated, so we'll filter by year ranges
            switch ($filters['category']) {
                case 'vintage':
                    $query->where('year', '<=', now()->year - 25);
                    break;
                case 'classic':
                    $query->whereBetween('year', [now()->year - 24, now()->year - 15]);
                    break;
                case 'modern':
                    $query->where('year', '>', now()->year - 15);
                    break;
            }
        }

        if (isset($filters['has_vin']) && $filters['has_vin'] !== '') {
            if ($filters['has_vin']) {
                $query->whereNotNull('vin');
            } else {
                $query->whereNull('vin');
            }
        }

        if (isset($filters['incomplete_data']) && $filters['incomplete_data']) {
            $query->where(function ($q) {
                $q->whereNull('vin')
                  ->orWhereNull('weight')
                  ->orWhereNull('length')
                  ->orWhereNull('width')
                  ->orWhereNull('height');
            });
        }

        return $query;
    }
}