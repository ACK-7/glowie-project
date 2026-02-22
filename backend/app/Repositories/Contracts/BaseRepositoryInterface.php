<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository Interface
 * 
 * Defines common repository methods for all entities
 */
interface BaseRepositoryInterface
{
    /**
     * Find a record by ID
     */
    public function find(int $id): ?Model;

    /**
     * Find a record by ID with relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?Model;

    /**
     * Find a record by ID or fail
     */
    public function findOrFail(int $id): Model;

    /**
     * Get all records
     */
    public function all(): Collection;

    /**
     * Get all records with relationships
     */
    public function allWithRelations(array $relations = []): Collection;

    /**
     * Create a new record
     */
    public function create(array $data): Model;

    /**
     * Update a record by ID
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a record by ID
     */
    public function delete(int $id): bool;

    /**
     * Get paginated results
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get filtered and paginated results
     */
    public function getFilteredPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Count total records
     */
    public function count(): int;

    /**
     * Count records with filters
     */
    public function countWithFilters(array $filters = []): int;

    /**
     * Check if record exists
     */
    public function exists(int $id): bool;

    /**
     * Get records by field value
     */
    public function getByField(string $field, $value): Collection;

    /**
     * Find first record by field value
     */
    public function findByField(string $field, $value): ?Model;

    /**
     * Get records with sorting
     */
    public function getAllSorted(string $sortBy = 'id', string $sortDirection = 'asc'): Collection;

    /**
     * Search records
     */
    public function search(string $query, array $fields = []): Collection;

    /**
     * Get records by date range
     */
    public function getByDateRange(string $dateField, string $startDate, string $endDate): Collection;

    /**
     * Bulk insert records
     */
    public function bulkInsert(array $data): bool;

    /**
     * Bulk update records
     */
    public function bulkUpdate(array $ids, array $data): int;

    /**
     * Get fresh model instance
     */
    public function getModel(): Model;
}