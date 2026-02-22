<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Base Repository Implementation
 * 
 * Provides common repository functionality for all entities
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Find a record by ID
     */
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Find a record by ID with relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?Model
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * Find a record by ID or fail
     */
    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Get all records
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get all records with relationships
     */
    public function allWithRelations(array $relations = []): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Create a new record
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record by ID
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Delete a record by ID
     */
    public function delete(int $id): bool
    {
        return $this->model->destroy($id) > 0;
    }

    /**
     * Get paginated results
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Get filtered and paginated results
     */
    public function getFilteredPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        $query = $this->applyFilters($query, $filters);
        
        return $query->paginate($perPage);
    }

    /**
     * Count total records
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Count records with filters
     */
    public function countWithFilters(array $filters = []): int
    {
        $query = $this->model->newQuery();
        $query = $this->applyFilters($query, $filters);
        
        return $query->count();
    }

    /**
     * Check if record exists
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Get records by field value
     */
    public function getByField(string $field, $value): Collection
    {
        return $this->model->where($field, $value)->get();
    }

    /**
     * Find first record by field value
     */
    public function findByField(string $field, $value): ?Model
    {
        return $this->model->where($field, $value)->first();
    }

    /**
     * Get records with sorting
     */
    public function getAllSorted(string $sortBy = 'id', string $sortDirection = 'asc'): Collection
    {
        return $this->model->orderBy($sortBy, $sortDirection)->get();
    }

    /**
     * Search records
     */
    public function search(string $query, array $fields = []): Collection
    {
        if (empty($fields)) {
            return new Collection();
        }

        $queryBuilder = $this->model->newQuery();
        
        $queryBuilder->where(function ($q) use ($query, $fields) {
            foreach ($fields as $field) {
                $q->orWhere($field, 'LIKE', "%{$query}%");
            }
        });

        return $queryBuilder->get();
    }

    /**
     * Get records by date range
     */
    public function getByDateRange(string $dateField, string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween($dateField, [$startDate, $endDate])->get();
    }

    /**
     * Bulk insert records
     */
    public function bulkInsert(array $data): bool
    {
        return $this->model->insert($data);
    }

    /**
     * Bulk update records
     */
    public function bulkUpdate(array $ids, array $data): int
    {
        return $this->model->whereIn('id', $ids)->update($data);
    }

    /**
     * Get fresh model instance
     */
    public function getModel(): Model
    {
        return $this->model->newInstance();
    }

    /**
     * Apply filters to query builder
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            switch ($key) {
                case 'search':
                    $this->applySearchFilter($query, $value);
                    break;
                    
                case 'sort_by':
                    $sortDirection = $filters['sort_direction'] ?? 'asc';
                    $query->orderBy($value, $sortDirection);
                    break;
                    
                case 'date_from':
                    $dateField = $filters['date_field'] ?? 'created_at';
                    $query->where($dateField, '>=', $value);
                    break;
                    
                case 'date_to':
                    $dateField = $filters['date_field'] ?? 'created_at';
                    $query->where($dateField, '<=', $value);
                    break;
                    
                case 'status':
                    if (is_array($value)) {
                        $query->whereIn('status', $value);
                    } else {
                        $query->where('status', $value);
                    }
                    break;
                    
                default:
                    // Handle custom filters in child repositories
                    $this->applyCustomFilter($query, $key, $value);
                    break;
            }
        }

        return $query;
    }

    /**
     * Apply search filter - to be overridden in child repositories
     */
    protected function applySearchFilter(Builder $query, string $searchTerm): void
    {
        // Default implementation - override in child repositories
    }

    /**
     * Apply custom filter - to be overridden in child repositories
     */
    protected function applyCustomFilter(Builder $query, string $key, $value): void
    {
        // Default implementation - override in child repositories
    }

    /**
     * Get records with complex joins and aggregations
     */
    protected function getWithJoins(array $joins = [], array $selects = ['*']): Collection
    {
        $query = $this->model->select($selects);
        
        foreach ($joins as $join) {
            $query->join($join['table'], $join['first'], $join['operator'] ?? '=', $join['second']);
        }
        
        return $query->get();
    }

    /**
     * Get aggregated data
     */
    protected function getAggregatedData(string $groupBy, array $aggregates = []): Collection
    {
        $query = $this->model->groupBy($groupBy);
        
        foreach ($aggregates as $aggregate) {
            $function = $aggregate['function']; // sum, count, avg, max, min
            $field = $aggregate['field'];
            $alias = $aggregate['alias'] ?? $function . '_' . $field;
            
            $query->selectRaw("{$function}({$field}) as {$alias}");
        }
        
        $query->addSelect($groupBy);
        
        return $query->get();
    }

    /**
     * Execute raw query with parameters
     */
    protected function executeRawQuery(string $sql, array $bindings = []): Collection
    {
        $results = DB::select($sql, $bindings);
        return collect($results);
    }

    /**
     * Get model table name
     */
    protected function getTableName(): string
    {
        return $this->model->getTable();
    }

    /**
     * Start database transaction
     */
    protected function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    /**
     * Commit database transaction
     */
    protected function commitTransaction(): void
    {
        DB::commit();
    }

    /**
     * Rollback database transaction
     */
    protected function rollbackTransaction(): void
    {
        DB::rollBack();
    }
}