<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_type_id',
        'make',
        'model',
        'year',
        'color',
        'vin',
        'license_plate',
        'engine_type',
        'transmission',
        'weight',
        'height',
        'length',
        'width',
        'description',
        'is_running',
    ];

    protected $casts = [
        'is_running' => 'boolean',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'year' => 'integer',
    ];

    protected $attributes = [
        'is_running' => true,
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($vehicle) {
            ActivityLog::logActivity('created', self::class, $vehicle->id, $vehicle->toArray());
        });

        static::updated(function ($vehicle) {
            if ($vehicle->wasChanged()) {
                ActivityLog::logActivity('updated', self::class, $vehicle->id, $vehicle->getChanges());
            }
        });
    }

    /**
     * Relationships
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Scopes
     */
    public function scopeRunning($query)
    {
        return $query->where('is_running', true);
    }

    public function scopeNotRunning($query)
    {
        return $query->where('is_running', false);
    }

    public function scopeByMake($query, $make)
    {
        return $query->where('make', 'like', "%{$make}%");
    }

    public function scopeByModel($query, $model)
    {
        return $query->where('model', 'like', "%{$model}%");
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByYearRange($query, $startYear, $endYear)
    {
        return $query->whereBetween('year', [$startYear, $endYear]);
    }

    /**
     * Accessors
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->year} {$this->make} {$this->model}";
    }

    public function getDisplayNameAttribute(): string
    {
        $name = $this->full_name;
        if ($this->color) {
            $name .= " ({$this->color})";
        }
        return $name;
    }

    public function getAgeAttribute(): int
    {
        return now()->year - $this->year;
    }

    public function getDimensionsAttribute(): array
    {
        return [
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    public function getVolumeAttribute(): ?float
    {
        if ($this->length && $this->width && $this->height) {
            return $this->length * $this->width * $this->height;
        }
        return null;
    }

    /**
     * Business Logic Methods
     */
    public function isVintage(): bool
    {
        return $this->age >= 25;
    }

    public function isClassic(): bool
    {
        return $this->age >= 15 && $this->age < 25;
    }

    public function isModern(): bool
    {
        return $this->age < 15;
    }

    public function getCategory(): string
    {
        if ($this->isVintage()) {
            return 'vintage';
        } elseif ($this->isClassic()) {
            return 'classic';
        }
        return 'modern';
    }

    public function getShippingClass(): string
    {
        // Determine shipping class based on dimensions and weight
        if ($this->weight > 3000 || $this->length > 6) {
            return 'oversized';
        } elseif ($this->weight > 2000 || $this->length > 5) {
            return 'large';
        } elseif ($this->weight > 1500 || $this->length > 4.5) {
            return 'medium';
        }
        return 'standard';
    }

    public function calculateShippingMultiplier(): float
    {
        $multipliers = [
            'standard' => 1.0,
            'medium' => 1.2,
            'large' => 1.5,
            'oversized' => 2.0,
        ];

        $baseMultiplier = $multipliers[$this->getShippingClass()] ?? 1.0;
        
        // Additional multiplier for non-running vehicles
        if (!$this->is_running) {
            $baseMultiplier *= 1.3;
        }
        
        // Additional multiplier for vintage vehicles (special handling)
        if ($this->isVintage()) {
            $baseMultiplier *= 1.4;
        }
        
        return $baseMultiplier;
    }

    public function getEstimatedValue(): ?float
    {
        // This would typically integrate with external APIs for vehicle valuation
        // For now, return null to indicate manual valuation needed
        return null;
    }

    public function hasValidVin(): bool
    {
        if (!$this->vin) {
            return false;
        }
        
        // Basic VIN validation (17 characters, no I, O, Q)
        return strlen($this->vin) === 17 && 
               !preg_match('/[IOQ]/', $this->vin) &&
               ctype_alnum($this->vin);
    }

    public function getRequiredDocuments(): array
    {
        $documents = [
            'invoice' => 'Purchase Invoice',
            'registration' => 'Vehicle Registration',
        ];

        if ($this->isVintage()) {
            $documents['authenticity'] = 'Authenticity Certificate';
        }

        if (!$this->is_running) {
            $documents['condition'] = 'Condition Report';
        }

        return $documents;
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        $currentYear = date('Y');
        
        return [
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => "required|integer|min:1900|max:{$currentYear}",
            'color' => 'nullable|string|max:50',
            'vin' => 'nullable|string|size:17|regex:/^[A-HJ-NPR-Z0-9]{17}$/',
            'license_plate' => 'nullable|string|max:20',
            'engine_type' => 'nullable|string|max:100',
            'transmission' => 'nullable|in:manual,automatic,cvt,semi-automatic',
            'weight' => 'nullable|numeric|min:0|max:10000',
            'height' => 'nullable|numeric|min:0|max:5',
            'length' => 'nullable|numeric|min:0|max:15',
            'width' => 'nullable|numeric|min:0|max:3',
            'is_running' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public static function updateValidationRules(): array
    {
        $rules = self::validationRules();
        
        // Make some fields optional for updates
        $rules['make'] = 'sometimes|' . $rules['make'];
        $rules['model'] = 'sometimes|' . $rules['model'];
        $rules['year'] = 'sometimes|' . $rules['year'];
        
        return $rules;
    }
}
