<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'image_url',
        'alt_text',
        'type',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public static function validationRules(): array
    {
        return [
            'car_id' => 'required|exists:cars,id',
            'image_url' => 'required|url|max:500',
            'alt_text' => 'nullable|string|max:200',
            'type' => 'required|in:exterior,interior,engine,other',
            'is_primary' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }
}