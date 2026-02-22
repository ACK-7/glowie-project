<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Activity Log Model
 * 
 * Tracks all user activities and system changes for audit purposes
 */
class ActivityLog extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should use updated_at timestamp.
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'changes',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relationships
     */

    /**
     * Get the user that performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model that was affected (polymorphic relationship)
     */
    public function model()
    {
        if ($this->model_type && $this->model_id) {
            return $this->model_type::find($this->model_id);
        }
        return null;
    }

    /**
     * Scopes
     */

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by model type
     */
    public function scopeByModelType($query, $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent activities
     */
    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Helper Methods
     */

    /**
     * Get formatted action description
     */
    public function getActionDescriptionAttribute(): string
    {
        $user = $this->user ? $this->user->full_name : 'System';
        $modelName = $this->model_type ? class_basename($this->model_type) : 'Unknown';
        
        return "{$user} {$this->action} {$modelName}" . ($this->model_id ? " (ID: {$this->model_id})" : '');
    }

    /**
     * Get changes summary
     */
    public function getChangesSummaryAttribute(): string
    {
        if (!$this->changes || empty($this->changes)) {
            return 'No changes recorded';
        }

        $summary = [];
        foreach ($this->changes as $field => $change) {
            if (is_array($change) && isset($change['old'], $change['new'])) {
                $summary[] = "{$field}: '{$change['old']}' → '{$change['new']}'";
            } else {
                $summary[] = "{$field}: {$change}";
            }
        }

        return implode(', ', $summary);
    }

    /**
     * Static helper to log activity
     */
    public static function logActivity(
        string $action,
        string $modelType = null,
        int $modelId = null,
        array $changes = [],
        int $userId = null
    ): self {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}