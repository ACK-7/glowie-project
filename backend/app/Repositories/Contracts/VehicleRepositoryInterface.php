<?php

namespace App\Repositories\Contracts;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

interface VehicleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find vehicle with relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?Vehicle;

    /**
     * Get vehicles by make
     */
    public function getByMake(string $make): Collection;

    /**
     * Get vehicles by model
     */
    public function getByModel(string $model): Collection;

    /**
     * Get vehicles by year
     */
    public function getByYear(int $year): Collection;

    /**
     * Get vehicles by year range
     */
    public function getByYearRange(int $startYear, int $endYear): Collection;

    /**
     * Get running vehicles
     */
    public function getRunning(): Collection;

    /**
     * Get non-running vehicles
     */
    public function getNotRunning(): Collection;

    /**
     * Get vehicles by category
     */
    public function getByCategory(string $category): Collection;

    /**
     * Get vehicles by shipping class
     */
    public function getByShippingClass(string $class): Collection;

    /**
     * Search vehicles
     */
    public function searchVehicles(string $query): Collection;

    /**
     * Get vehicle statistics
     */
    public function getStatistics(): array;

    /**
     * Get popular makes
     */
    public function getPopularMakes(int $limit = 10): array;

    /**
     * Get popular models
     */
    public function getPopularModels(int $limit = 10): array;

    /**
     * Get recent vehicles
     */
    public function getRecent(int $limit = 10): Collection;

    /**
     * Get vehicles requiring attention
     */
    public function getRequiringAttention(): Collection;
}