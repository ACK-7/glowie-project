<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key_name',
        'value',
        'data_type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // Data type constants
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';

    const VALID_TYPES = [
        self::TYPE_STRING,
        self::TYPE_INTEGER,
        self::TYPE_BOOLEAN,
        self::TYPE_JSON,
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            // Clear cache when setting is updated
            Cache::forget("system_setting_{$setting->key_name}");
            Cache::forget('system_settings_all');
            
            ActivityLog::logActivity('updated', self::class, $setting->id, [
                'key' => $setting->key_name,
                'old_value' => $setting->getOriginal('value'),
                'new_value' => $setting->value,
            ]);
        });

        static::deleted(function ($setting) {
            // Clear cache when setting is deleted
            Cache::forget("system_setting_{$setting->key_name}");
            Cache::forget('system_settings_all');
            
            ActivityLog::logActivity('deleted', self::class, $setting->id, [
                'key' => $setting->key_name,
                'value' => $setting->value,
            ]);
        });
    }

    /**
     * Scopes
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('data_type', $type);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('key_name', $key);
    }

    /**
     * Accessors
     */
    public function getTypedValueAttribute()
    {
        return $this->castValue($this->value, $this->data_type);
    }

    /**
     * Mutators
     */
    public function setValueAttribute($value)
    {
        // Convert value to string for storage
        if (is_array($value) || is_object($value)) {
            $this->attributes['value'] = json_encode($value);
            $this->attributes['data_type'] = self::TYPE_JSON;
        } elseif (is_bool($value)) {
            $this->attributes['value'] = $value ? '1' : '0';
            $this->attributes['data_type'] = self::TYPE_BOOLEAN;
        } elseif (is_numeric($value) && !is_string($value)) {
            $this->attributes['value'] = (string) $value;
            $this->attributes['data_type'] = self::TYPE_INTEGER;
        } else {
            $this->attributes['value'] = (string) $value;
            $this->attributes['data_type'] = self::TYPE_STRING;
        }
    }

    /**
     * Business Logic Methods
     */
    private function castValue($value, $type)
    {
        switch ($type) {
            case self::TYPE_INTEGER:
                return (int) $value;
                
            case self::TYPE_BOOLEAN:
                return (bool) $value;
                
            case self::TYPE_JSON:
                return json_decode($value, true);
                
            case self::TYPE_STRING:
            default:
                return (string) $value;
        }
    }

    /**
     * Static helper methods for easy access
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("system_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key_name', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            return $setting->typed_value;
        });
    }

    public static function set(string $key, $value, string $description = null, bool $isPublic = false): self
    {
        $setting = self::updateOrCreate(
            ['key_name' => $key],
            [
                'value' => $value,
                'description' => $description,
                'is_public' => $isPublic,
            ]
        );

        return $setting;
    }

    public static function has(string $key): bool
    {
        return self::where('key_name', $key)->exists();
    }

    public static function remove(string $key): bool
    {
        return self::where('key_name', $key)->delete() > 0;
    }

    public static function getAll(bool $publicOnly = false): array
    {
        $cacheKey = $publicOnly ? 'system_settings_public' : 'system_settings_all';
        
        return Cache::remember($cacheKey, 3600, function () use ($publicOnly) {
            $query = self::query();
            
            if ($publicOnly) {
                $query->where('is_public', true);
            }
            
            return $query->get()->mapWithKeys(function ($setting) {
                return [$setting->key_name => $setting->typed_value];
            })->toArray();
        });
    }

    public static function getByCategory(string $category): array
    {
        return Cache::remember("system_settings_category_{$category}", 3600, function () use ($category) {
            return self::where('key_name', 'like', "{$category}.%")
                      ->get()
                      ->mapWithKeys(function ($setting) {
                          return [$setting->key_name => $setting->typed_value];
                      })
                      ->toArray();
        });
    }

    public static function setMultiple(array $settings): void
    {
        foreach ($settings as $key => $config) {
            if (is_array($config)) {
                self::set(
                    $key,
                    $config['value'],
                    $config['description'] ?? null,
                    $config['is_public'] ?? false
                );
            } else {
                self::set($key, $config);
            }
        }
    }

    /**
     * Default system settings
     */
    public static function getDefaultSettings(): array
    {
        return [
            // Application settings
            'app.name' => [
                'value' => 'ShipWithGlowie Auto Admin',
                'description' => 'Application name displayed in the interface',
                'is_public' => true,
            ],
            'app.timezone' => [
                'value' => 'UTC',
                'description' => 'Default application timezone',
                'is_public' => false,
            ],
            'app.locale' => [
                'value' => 'en',
                'description' => 'Default application locale',
                'is_public' => true,
            ],
            
            // Business settings
            'business.default_currency' => [
                'value' => 'USD',
                'description' => 'Default currency for pricing',
                'is_public' => true,
            ],
            'business.quote_validity_days' => [
                'value' => 30,
                'description' => 'Default quote validity period in days',
                'is_public' => false,
            ],
            'business.payment_terms_days' => [
                'value' => 30,
                'description' => 'Default payment terms in days',
                'is_public' => false,
            ],
            
            // Notification settings
            'notifications.email_enabled' => [
                'value' => true,
                'description' => 'Enable email notifications',
                'is_public' => false,
            ],
            'notifications.sms_enabled' => [
                'value' => false,
                'description' => 'Enable SMS notifications',
                'is_public' => false,
            ],
            'notifications.admin_email' => [
                'value' => 'admin@shipwithglowie.com',
                'description' => 'Admin email for system notifications',
                'is_public' => false,
            ],
            
            // File upload settings
            'uploads.max_file_size' => [
                'value' => 10485760, // 10MB
                'description' => 'Maximum file upload size in bytes',
                'is_public' => false,
            ],
            'uploads.allowed_types' => [
                'value' => ['pdf', 'jpg', 'jpeg', 'png', 'gif'],
                'description' => 'Allowed file types for uploads',
                'is_public' => false,
            ],
            
            // Security settings
            'security.session_timeout' => [
                'value' => 120, // 2 hours - too long for admin sessions
                'description' => 'Session timeout in minutes',
                'is_public' => false,
            ],
            'security.max_login_attempts' => [
                'value' => 5,
                'description' => 'Maximum login attempts before lockout',
                'is_public' => false,
            ],
            'security.lockout_duration' => [
                'value' => 15, // 15 minutes
                'description' => 'Account lockout duration in minutes',
                'is_public' => false,
            ],
            
            // API settings
            'api.rate_limit' => [
                'value' => 60,
                'description' => 'API rate limit per minute',
                'is_public' => false,
            ],
            'api.pagination_limit' => [
                'value' => 50,
                'description' => 'Maximum items per page in API responses',
                'is_public' => false,
            ],
            
            // Maintenance settings
            'maintenance.mode' => [
                'value' => false,
                'description' => 'Enable maintenance mode',
                'is_public' => true,
            ],
            'maintenance.message' => [
                'value' => 'System is under maintenance. Please try again later.',
                'description' => 'Maintenance mode message',
                'is_public' => true,
            ],
        ];
    }

    public static function initializeDefaults(): void
    {
        $defaults = self::getDefaultSettings();
        
        foreach ($defaults as $key => $config) {
            if (!self::has($key)) {
                self::set(
                    $key,
                    $config['value'],
                    $config['description'],
                    $config['is_public']
                );
            }
        }
    }

    /**
     * Helper methods for common settings
     */
    public static function getAppName(): string
    {
        return self::get('app.name', 'ShipWithGlowie Auto Admin');
    }

    public static function getDefaultCurrency(): string
    {
        return self::get('business.default_currency', 'USD');
    }

    public static function getQuoteValidityDays(): int
    {
        return self::get('business.quote_validity_days', 30);
    }

    public static function getPaymentTermsDays(): int
    {
        return self::get('business.payment_terms_days', 30);
    }

    public static function isMaintenanceMode(): bool
    {
        return self::get('maintenance.mode', false);
    }

    public static function getMaintenanceMessage(): string
    {
        return self::get('maintenance.message', 'System is under maintenance. Please try again later.');
    }

    public static function isEmailNotificationEnabled(): bool
    {
        return self::get('notifications.email_enabled', true);
    }

    public static function isSmsNotificationEnabled(): bool
    {
        return self::get('notifications.sms_enabled', false);
    }

    public static function getMaxFileSize(): int
    {
        return self::get('uploads.max_file_size', 10485760);
    }

    public static function getAllowedFileTypes(): array
    {
        return self::get('uploads.allowed_types', ['pdf', 'jpg', 'jpeg', 'png', 'gif']);
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'key_name' => 'required|string|max:100|unique:system_settings,key_name',
            'value' => 'nullable',
            'data_type' => 'required|in:' . implode(',', self::VALID_TYPES),
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
        ];
    }

    public static function updateValidationRules($id = null): array
    {
        $rules = self::validationRules();
        
        if ($id) {
            $rules['key_name'] = 'required|string|max:100|unique:system_settings,key_name,' . $id;
        }
        
        return $rules;
    }
}