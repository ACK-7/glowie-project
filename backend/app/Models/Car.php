<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'category_id',
        'model',
        'year',
        'color',
        'vin',
        'description',
        'engine_type',
        'fuel_type',
        'transmission',
        'mileage',
        'drive_type',
        'doors',
        'seats',
        'length',
        'width',
        'height',
        'weight',
        'price',
        'currency',
        'location_country',
        'location_city',
        'dealer_name',
        'dealer_contact',
        'estimated_shipping_days_min',
        'estimated_shipping_days_max',
        'shipping_cost',
        'condition',
        'status',
        'is_featured',
        'is_running',
        'features',
        'safety_features',
        'slug',
        'meta_description',
        'tags',
        'rating',
        'views_count',
        'inquiries_count',
        'featured_until',
    ];

    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
        'doors' => 'integer',
        'seats' => 'integer',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'price' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'estimated_shipping_days_min' => 'integer',
        'estimated_shipping_days_max' => 'integer',
        'is_featured' => 'boolean',
        'is_running' => 'boolean',
        'features' => 'array',
        'safety_features' => 'array',
        'tags' => 'array',
        'rating' => 'decimal:2',
        'views_count' => 'integer',
        'inquiries_count' => 'integer',
        'featured_until' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($car) {
            if (empty($car->slug)) {
                $car->slug = $car->generateSlug();
            }
        });
        
        static::updating(function ($car) {
            if ($car->isDirty(['brand_id', 'model', 'year']) && empty($car->slug)) {
                $car->slug = $car->generateSlug();
            }
        });
    }

    // Relationships
    public function brand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class, 'brand_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CarCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(CarImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(CarImage::class)->where('is_primary', true);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                    ->where(function ($q) {
                        $q->whereNull('featured_until')
                          ->orWhere('featured_until', '>', now());
                    });
    }

    public function scopeByBrand($query, $brandSlug)
    {
        return $query->whereHas('brand', function ($q) use ($brandSlug) {
            $q->where('slug', $brandSlug);
        });
    }

    public function scopeByCategory($query, $categorySlug)
    {
        return $query->whereHas('category', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    public function scopeByCondition($query, $condition)
    {
        return $query->where('condition', $condition);
    }

    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    public function scopeByYearRange($query, $minYear, $maxYear)
    {
        return $query->whereBetween('year', [$minYear, $maxYear]);
    }

    public function scopeByLocation($query, $country)
    {
        return $query->where('location_country', $country);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('model', 'like', "%{$search}%")
              ->orWhereHas('brand', function ($brandQuery) use ($search) {
                  $brandQuery->where('name', 'like', "%{$search}%");
              })
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->year} {$this->brand->name} {$this->model}";
    }

    public function getDisplayNameAttribute(): string
    {
        $name = $this->full_name;
        if ($this->color) {
            $name .= " ({$this->color})";
        }
        return $name;
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0) . ' ' . $this->currency;
    }

    public function getShippingTimeRangeAttribute(): ?string
    {
        if ($this->estimated_shipping_days_min && $this->estimated_shipping_days_max) {
            return "{$this->estimated_shipping_days_min}-{$this->estimated_shipping_days_max} days";
        }
        return null;
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primaryImage = $this->images()->where('is_primary', true)->first();
        return $primaryImage ? $primaryImage->image_url : null;
    }

    public function getAgeAttribute(): int
    {
        return now()->year - $this->year;
    }

    public function getIsNewAttribute(): bool
    {
        return $this->condition === 'new' || $this->age <= 1;
    }

    // Methods
    public function generateSlug(): string
    {
        $baseSlug = Str::slug($this->year . '-' . $this->brand->name . '-' . $this->model);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function incrementInquiries(): void
    {
        $this->increment('inquiries_count');
    }

    public function markAsFeatured($until = null): void
    {
        $this->update([
            'is_featured' => true,
            'featured_until' => $until,
        ]);
    }

    public function removeFeatured(): void
    {
        $this->update([
            'is_featured' => false,
            'featured_until' => null,
        ]);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isFeaturedActive(): bool
    {
        return $this->is_featured && 
               (is_null($this->featured_until) || $this->featured_until->isFuture());
    }

    // Validation Rules
    public static function validationRules(): array
    {
        $currentYear = date('Y') + 1; // Allow next year's models
        
        return [
            'brand_id' => 'required|exists:car_brands,id',
            'category_id' => 'required|exists:car_categories,id',
            'model' => 'required|string|max:100',
            'year' => "required|integer|min:1900|max:{$currentYear}",
            'color' => 'nullable|string|max:50',
            'vin' => 'nullable|string|max:17',
            'description' => 'nullable|string|max:2000',
            'engine_type' => 'nullable|string|max:100',
            'fuel_type' => 'nullable|in:petrol,diesel,hybrid,electric,lpg',
            'transmission' => 'nullable|in:manual,automatic,cvt,semi-automatic',
            'mileage' => 'nullable|integer|min:0|max:1000000',
            'drive_type' => 'nullable|in:fwd,rwd,awd,4wd',
            'doors' => 'nullable|integer|min:2|max:6',
            'seats' => 'nullable|integer|min:1|max:9',
            'price' => 'required|numeric|min:0|max:999999999.99',
            'currency' => 'required|string|size:3',
            'location_country' => 'required|string|max:100',
            'location_city' => 'nullable|string|max:100',
            'condition' => 'required|in:new,used,certified_pre_owned',
            'status' => 'required|in:available,sold,reserved,in_transit,inactive',
            'is_featured' => 'boolean',
            'is_running' => 'boolean',
            'features' => 'nullable|array',
            'safety_features' => 'nullable|array',
            'tags' => 'nullable|array',
        ];
    }
}