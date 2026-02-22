<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'password_is_temporary',
        'country',
        'city',
        'address',
        'postal_code',
        'profile_image_url',
        'date_of_birth',
        'id_number',
        'id_type',
        'is_verified',
        'is_active',
        'status',
        'email_verified_at',
        'notes',
        'total_bookings',
        'total_spent',
        'last_login_at',
        'preferred_language',
        'newsletter_subscribed'
    ];

    protected $hidden = [
        'password',
        'verification_token',
        'reset_token',
        'reset_token_expires_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'password_is_temporary' => 'boolean',
        'total_spent' => 'decimal:2',
        'newsletter_subscribed' => 'boolean',
        'last_login_at' => 'datetime',
        'reset_token_expires_at' => 'datetime',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($customer) {
            ActivityLog::logActivity('created', self::class, $customer->id, $customer->toArray());
        });

        static::updated(function ($customer) {
            if ($customer->wasChanged()) {
                ActivityLog::logActivity('updated', self::class, $customer->id, $customer->getChanges());
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

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function notifications(): HasMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Accessors & Mutators
     */
    
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Scopes
     */
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Methods
     */
    
    public function incrementBookingCount()
    {
        $this->increment('total_bookings');
    }

    public function addToTotalSpent($amount)
    {
        $this->increment('total_spent', $amount);
    }

    /**
     * Enhanced Business Logic Methods
     */
    public function getCustomerTier(): string
    {
        if ($this->total_spent >= 50000) {
            return 'platinum';
        } elseif ($this->total_spent >= 25000) {
            return 'gold';
        } elseif ($this->total_spent >= 10000) {
            return 'silver';
        }
        return 'bronze';
    }

    public function getDiscountPercentage(): float
    {
        $discounts = [
            'platinum' => 15.0,
            'gold' => 10.0,
            'silver' => 5.0,
            'bronze' => 0.0,
        ];

        return $discounts[$this->getCustomerTier()];
    }

    public function hasActiveBookings(): bool
    {
        return $this->bookings()
                   ->whereNotIn('status', [Booking::STATUS_DELIVERED, Booking::STATUS_CANCELLED])
                   ->exists();
    }

    public function getPendingPayments(): float
    {
        return $this->payments()
                   ->where('status', Payment::STATUS_PENDING)
                   ->sum('amount');
    }

    public function getAverageBookingValue(): float
    {
        if ($this->total_bookings == 0) {
            return 0;
        }
        
        return $this->total_spent / $this->total_bookings;
    }

    public function getLastBookingDate(): ?string
    {
        $lastBooking = $this->bookings()->latest()->first();
        return $lastBooking ? $lastBooking->created_at->format('Y-m-d') : null;
    }

    public function updateLastLogin(): void
    {
        $this->last_login_at = now();
        $this->save();
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

    public function verify(): bool
    {
        $this->is_verified = true;
        $this->email_verified_at = now();
        return $this->save();
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'id_number' => 'nullable|string|max:50',
            'id_type' => 'nullable|string|max:50',
            'preferred_language' => 'nullable|string|max:10',
            'newsletter_subscribed' => 'boolean',
        ];
    }

    public static function updateValidationRules($id = null): array
    {
        $rules = [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:customers,email' . ($id ? ",{$id}" : ''),
            'phone' => 'sometimes|required|string|max:20',
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'id_number' => 'nullable|string|max:50',
            'id_type' => 'nullable|string|max:50',
            'preferred_language' => 'nullable|string|max:10',
            'newsletter_subscribed' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'is_verified' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:active,inactive,suspended',
        ];
        
        return $rules;
    }
}
