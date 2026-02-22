<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'origin_country',
        'origin_city',
        'destination_country',
        'destination_city',
        'base_price',
        'estimated_days',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
        'estimated_days' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($route) {
            ActivityLog::logActivity('created', self::class, $route->id, $route->toArray());
        });

        static::updated(function ($route) {
            if ($route->wasChanged()) {
                ActivityLog::logActivity('updated', self::class, $route->id, $route->getChanges());
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

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
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

    public function scopeFromCountry($query, $country)
    {
        return $query->where('origin_country', 'like', "%{$country}%");
    }

    public function scopeToCountry($query, $country)
    {
        return $query->where('destination_country', 'like', "%{$country}%");
    }

    public function scopeFromCity($query, $city)
    {
        return $query->where('origin_city', 'like', "%{$city}%");
    }

    public function scopeToCity($query, $city)
    {
        return $query->where('destination_city', 'like', "%{$city}%");
    }

    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('base_price', [$minPrice, $maxPrice]);
    }

    public function scopeByDuration($query, $maxDays)
    {
        return $query->where('estimated_days', '<=', $maxDays);
    }

    /**
     * Accessors
     */
    public function getFullRouteAttribute(): string
    {
        return "{$this->origin_city}, {$this->origin_country} → {$this->destination_city}, {$this->destination_country}";
    }

    public function getOriginAttribute(): string
    {
        return "{$this->origin_city}, {$this->origin_country}";
    }

    public function getDestinationAttribute(): string
    {
        return "{$this->destination_city}, {$this->destination_country}";
    }

    public function getDistanceKmAttribute(): ?int
    {
        // This would typically be calculated using geographic coordinates
        // For now, return estimated distances for common routes
        $distances = [
            'Japan-Uganda' => 12500,
            'UK-Uganda' => 6800,
            'UAE-Uganda' => 4200,
        ];

        $routeKey = "{$this->origin_country}-{$this->destination_country}";
        return $distances[$routeKey] ?? null;
    }

    public function getIsInternationalAttribute(): bool
    {
        return strtolower($this->origin_country) !== strtolower($this->destination_country);
    }

    public function getEstimatedWeeksAttribute(): float
    {
        return round($this->estimated_days / 7, 1);
    }

    /**
     * Business Logic Methods
     */
    public function calculatePrice(Vehicle $vehicle): float
    {
        $basePrice = $this->base_price;
        $vehicleMultiplier = $vehicle->calculateShippingMultiplier();
        
        return $basePrice * $vehicleMultiplier;
    }

    public function getRequiredDocuments(): array
    {
        $documents = [
            'passport' => 'Valid Passport',
            'invoice' => 'Purchase Invoice',
        ];

        if ($this->is_international) {
            $documents['customs'] = 'Customs Declaration';
            $documents['insurance'] = 'International Insurance';
        }

        // Country-specific requirements
        switch (strtolower($this->destination_country)) {
            case 'uganda':
                $documents['import_permit'] = 'Import Permit';
                $documents['tax_clearance'] = 'Tax Clearance Certificate';
                break;
        }

        return $documents;
    }

    public function getShippingMethods(): array
    {
        $methods = [];

        if ($this->is_international) {
            $methods['sea_freight'] = [
                'name' => 'Sea Freight',
                'duration_days' => $this->estimated_days,
                'cost_multiplier' => 1.0,
                'description' => 'Most economical option for international shipping',
            ];

            $methods['air_freight'] = [
                'name' => 'Air Freight',
                'duration_days' => max(3, intval($this->estimated_days * 0.1)),
                'cost_multiplier' => 4.0,
                'description' => 'Fastest but most expensive option',
            ];
        } else {
            $methods['road_transport'] = [
                'name' => 'Road Transport',
                'duration_days' => $this->estimated_days,
                'cost_multiplier' => 1.0,
                'description' => 'Direct road transport',
            ];
        }

        return $methods;
    }

    public function getPopularityScore(): int
    {
        // Calculate based on number of bookings in the last 6 months
        $recentBookings = $this->bookings()
            ->where('created_at', '>=', now()->subMonths(6))
            ->count();

        if ($recentBookings >= 50) return 5;
        if ($recentBookings >= 30) return 4;
        if ($recentBookings >= 15) return 3;
        if ($recentBookings >= 5) return 2;
        return 1;
    }

    public function getSeasonalPricing(): array
    {
        // This would typically be more sophisticated based on historical data
        return [
            'peak_season' => [
                'months' => ['December', 'January', 'July', 'August'],
                'multiplier' => 1.2,
                'description' => 'High demand period',
            ],
            'low_season' => [
                'months' => ['February', 'March', 'September', 'October'],
                'multiplier' => 0.9,
                'description' => 'Lower demand period',
            ],
            'regular_season' => [
                'months' => ['April', 'May', 'June', 'November'],
                'multiplier' => 1.0,
                'description' => 'Standard pricing',
            ],
        ];
    }

    public function getCurrentSeasonMultiplier(): float
    {
        $currentMonth = now()->format('F');
        $pricing = $this->getSeasonalPricing();

        foreach ($pricing as $season) {
            if (in_array($currentMonth, $season['months'])) {
                return $season['multiplier'];
            }
        }

        return 1.0; // Default multiplier
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

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'origin_country' => 'required|string|max:100',
            'origin_city' => 'required|string|max:100',
            'destination_country' => 'required|string|max:100',
            'destination_city' => 'required|string|max:100',
            'base_price' => 'required|numeric|min:0',
            'estimated_days' => 'required|integer|min:1|max:365',
            'is_active' => 'boolean',
        ];
    }

    public static function updateValidationRules(): array
    {
        $rules = self::validationRules();
        
        // Make fields optional for updates
        foreach ($rules as $field => $rule) {
            if ($field !== 'is_active') {
                $rules[$field] = 'sometimes|' . $rule;
            }
        }
        
        return $rules;
    }
}
