<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'role',
        'permissions',
        'is_active',
        'last_login_at',
        'phone',
        'profile_image_url'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'permissions' => 'array',
        'password' => 'hashed',
    ];

    protected $attributes = [
        'role' => 'operator',
        'is_active' => true,
    ];

    // Role constants
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_OPERATOR = 'operator';

    const VALID_ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
        self::ROLE_OPERATOR,
    ];

    // Permission constants
    const PERMISSIONS = [
        'bookings.view' => 'View bookings',
        'bookings.create' => 'Create bookings',
        'bookings.edit' => 'Edit bookings',
        'bookings.delete' => 'Delete bookings',
        'quotes.view' => 'View quotes',
        'quotes.create' => 'Create quotes',
        'quotes.edit' => 'Edit quotes',
        'quotes.approve' => 'Approve quotes',
        'quotes.delete' => 'Delete quotes',
        'customers.view' => 'View customers',
        'customers.create' => 'Create customers',
        'customers.edit' => 'Edit customers',
        'customers.delete' => 'Delete customers',
        'shipments.view' => 'View shipments',
        'shipments.create' => 'Create shipments',
        'shipments.edit' => 'Edit shipments',
        'shipments.track' => 'Track shipments',
        'documents.view' => 'View documents',
        'documents.upload' => 'Upload documents',
        'documents.verify' => 'Verify documents',
        'documents.delete' => 'Delete documents',
        'payments.view' => 'View payments',
        'payments.create' => 'Create payments',
        'payments.edit' => 'Edit payments',
        'payments.refund' => 'Process refunds',
        'analytics.view' => 'View analytics',
        'analytics.export' => 'Export reports',
        'users.view' => 'View users',
        'users.create' => 'Create users',
        'users.edit' => 'Edit users',
        'users.delete' => 'Delete users',
        'settings.view' => 'View settings',
        'settings.edit' => 'Edit settings',
    ];

    // Default role permissions
    const ROLE_PERMISSIONS = [
        self::ROLE_SUPER_ADMIN => [], // Super admin has all permissions
        self::ROLE_ADMIN => [
            'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.delete',
            'quotes.view', 'quotes.create', 'quotes.edit', 'quotes.approve', 'quotes.delete',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'shipments.view', 'shipments.create', 'shipments.edit', 'shipments.track',
            'documents.view', 'documents.upload', 'documents.verify', 'documents.delete',
            'payments.view', 'payments.create', 'payments.edit', 'payments.refund',
            'analytics.view', 'analytics.export',
            'users.view', 'users.create', 'users.edit',
            'settings.view',
        ],
        self::ROLE_MANAGER => [
            'bookings.view', 'bookings.create', 'bookings.edit',
            'quotes.view', 'quotes.create', 'quotes.edit', 'quotes.approve',
            'customers.view', 'customers.create', 'customers.edit',
            'shipments.view', 'shipments.create', 'shipments.edit', 'shipments.track',
            'documents.view', 'documents.upload', 'documents.verify',
            'payments.view', 'payments.create', 'payments.edit',
            'analytics.view', 'analytics.export',
        ],
        self::ROLE_OPERATOR => [
            'bookings.view', 'bookings.create', 'bookings.edit',
            'quotes.view', 'quotes.create', 'quotes.edit',
            'customers.view', 'customers.create', 'customers.edit',
            'shipments.view', 'shipments.edit', 'shipments.track',
            'documents.view', 'documents.upload',
            'payments.view',
        ],
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'full_name',
        'role_label',
        'initials',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Set default permissions based on role
            if (empty($user->permissions) && $user->role) {
                $user->permissions = self::ROLE_PERMISSIONS[$user->role] ?? [];
            }
        });

        static::created(function ($user) {
            ActivityLog::logActivity('created', self::class, $user->id, $user->toArray());
        });

        static::updated(function ($user) {
            if ($user->wasChanged()) {
                ActivityLog::logActivity('updated', self::class, $user->id, $user->getChanges());
            }
        });
    }

    /**
     * Relationships
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function createdBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'created_by');
    }

    public function updatedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'updated_by');
    }

    public function createdQuotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'created_by');
    }

    public function approvedQuotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'approved_by');
    }

    public function verifiedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'verified_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeSuperAdmins($query)
    {
        return $query->where('role', self::ROLE_SUPER_ADMIN);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    public function scopeManagers($query)
    {
        return $query->where('role', self::ROLE_MANAGER);
    }

    public function scopeOperators($query)
    {
        return $query->where('role', self::ROLE_OPERATOR);
    }

    /**
     * Accessors
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getFirstNameAttribute(): string
    {
        $parts = explode(' ', $this->name, 2);
        return $parts[0] ?? '';
    }

    public function getLastNameAttribute(): string
    {
        $parts = explode(' ', $this->name, 2);
        return $parts[1] ?? '';
    }

    public function getRoleLabelAttribute(): string
    {
        $labels = [
            self::ROLE_SUPER_ADMIN => 'Super Administrator',
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_OPERATOR => 'Operator',
        ];

        return $labels[$this->role] ?? 'Unknown';
    }

    public function getInitialsAttribute(): string
    {
        $parts = explode(' ', $this->name);
        $first = strtoupper(substr($parts[0] ?? '', 0, 1));
        $last = strtoupper(substr($parts[1] ?? '', 0, 1));
        return $first . $last;
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->last_login_at && $this->last_login_at->diffInMinutes(now()) <= 15;
    }

    public function getLastSeenAttribute(): string
    {
        if (!$this->last_login_at) {
            return 'Never';
        }
        
        if ($this->is_online) {
            return 'Online';
        }
        
        return $this->last_login_at->diffForHumans();
    }

    /**
     * Business Logic Methods
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    public function hasPermission(string $permission): bool
    {
        // Super admins have all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check if user has the specific permission
        return in_array($permission, $this->permissions ?? []);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }

    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        
        return true;
    }

    public function grantPermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            $permissions = $this->permissions ?? [];
            $permissions[] = $permission;
            $this->permissions = $permissions;
        }
    }

    public function revokePermission(string $permission): void
    {
        if ($this->hasPermission($permission)) {
            $permissions = array_diff($this->permissions ?? [], [$permission]);
            $this->permissions = array_values($permissions);
        }
    }

    public function syncPermissions(array $permissions): void
    {
        $this->permissions = array_values(array_intersect($permissions, array_keys(self::PERMISSIONS)));
    }

    public function assignRole(string $role): bool
    {
        if (!in_array($role, self::VALID_ROLES)) {
            return false;
        }

        $this->role = $role;
        $this->permissions = self::ROLE_PERMISSIONS[$role] ?? [];
        
        return $this->save();
    }

    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }

    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    public function updateLastLogin(): void
    {
        $this->last_login_at = now();
        $this->save();
    }

    public function canManageUser(User $user): bool
    {
        // Super admins can manage anyone
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Admins can manage managers and operators
        if ($this->isAdmin()) {
            return in_array($user->role, [self::ROLE_MANAGER, self::ROLE_OPERATOR]);
        }
        
        // Managers can manage operators
        if ($this->isManager()) {
            return $user->role === self::ROLE_OPERATOR;
        }
        
        return false;
    }

    public function getAvailablePermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return self::PERMISSIONS;
        }
        
        // Return permissions available for the user's role and below
        $availablePermissions = [];
        $roleHierarchy = [
            self::ROLE_ADMIN => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_OPERATOR],
            self::ROLE_MANAGER => [self::ROLE_MANAGER, self::ROLE_OPERATOR],
            self::ROLE_OPERATOR => [self::ROLE_OPERATOR],
        ];
        
        $allowedRoles = $roleHierarchy[$this->role] ?? [];
        
        foreach ($allowedRoles as $role) {
            $rolePermissions = self::ROLE_PERMISSIONS[$role] ?? [];
            foreach ($rolePermissions as $permission) {
                $availablePermissions[$permission] = self::PERMISSIONS[$permission] ?? $permission;
            }
        }
        
        return $availablePermissions;
    }

    public function getActivitySummary(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'total_actions' => $this->activityLogs()->where('created_at', '>=', $startDate)->count(),
            'bookings_created' => $this->createdBookings()->where('created_at', '>=', $startDate)->count(),
            'quotes_created' => $this->createdQuotes()->where('created_at', '>=', $startDate)->count(),
            'quotes_approved' => $this->approvedQuotes()->where('approved_at', '>=', $startDate)->count(),
            'documents_verified' => $this->verifiedDocuments()->where('verified_at', '>=', $startDate)->count(),
        ];
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:' . implode(',', self::VALID_ROLES),
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(self::PERMISSIONS)),
            'is_active' => 'boolean',
            'phone' => 'nullable|string|max:20',
        ];
    }

    public static function updateValidationRules($id = null): array
    {
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email' . ($id ? ",{$id}" : ''),
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'role' => 'sometimes|required|in:' . implode(',', self::VALID_ROLES),
            'permissions' => 'sometimes|nullable|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(self::PERMISSIONS)),
            'is_active' => 'sometimes|boolean',
            'phone' => 'sometimes|nullable|string|max:20',
        ];
        
        return $rules;
    }
}
