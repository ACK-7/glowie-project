<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'customer_id',
        'document_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'expiry_date',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'verified_at' => 'datetime',
        'expiry_date' => 'date',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    // Document type constants
    const TYPE_PASSPORT = 'passport';
    const TYPE_LICENSE = 'license';
    const TYPE_INVOICE = 'invoice';
    const TYPE_INSURANCE = 'insurance';
    const TYPE_CUSTOMS = 'customs';
    const TYPE_OTHER = 'other';

    const VALID_TYPES = [
        self::TYPE_PASSPORT,
        self::TYPE_LICENSE,
        self::TYPE_INVOICE,
        self::TYPE_INSURANCE,
        self::TYPE_CUSTOMS,
        self::TYPE_OTHER,
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
    ];

    // Allowed file types
    const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    // Maximum file size (in bytes) - 10MB
    const MAX_FILE_SIZE = 10485760;

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($document) {
            ActivityLog::logActivity('created', self::class, $document->id, $document->toArray());
        });

        static::updated(function ($document) {
            if ($document->wasChanged()) {
                ActivityLog::logActivity('updated', self::class, $document->id, $document->getChanges());
            }
        });

        static::deleting(function ($document) {
            // Delete the physical file when document is deleted
            if ($document->file_path && Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }
        });
    }

    /**
     * Relationships
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scopes
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now())
                    ->orWhere('status', self::STATUS_EXPIRED);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByBooking($query, $bookingId)
    {
        return $query->where('booking_id', $bookingId);
    }

    public function scopeExpiringWithin($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>', now())
                    ->where('status', self::STATUS_APPROVED);
    }

    /**
     * Accessors
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        if (!$this->expiry_date) {
            return 0;
        }
        
        return max(0, now()->diffInDays($this->expiry_date, false));
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            self::TYPE_PASSPORT => 'Passport',
            self::TYPE_LICENSE => 'Driving License',
            self::TYPE_INVOICE => 'Purchase Invoice',
            self::TYPE_INSURANCE => 'Insurance Certificate',
            self::TYPE_CUSTOMS => 'Customs Declaration',
            self::TYPE_OTHER => 'Other Document',
        ];

        return $labels[$this->document_type] ?? 'Unknown';
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' bytes';
    }

    public function getFileUrlAttribute(): string
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            return Storage::url($this->file_path);
        }
        
        return '';
    }

    public function getIsImageAttribute(): bool
    {
        return in_array($this->mime_type, [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
        ]);
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Business Logic Methods
     */
    public function approve(int $userId = null, string $notes = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_APPROVED;
        $this->verified_by = $userId ?? auth()->id();
        $this->verified_at = now();
        $this->rejection_reason = null; // Clear any previous rejection reason

        if ($this->save()) {
            ActivityLog::logActivity(
                'approved',
                self::class,
                $this->id,
                ['approved_by' => $this->verified_by, 'notes' => $notes]
            );
            
            return true;
        }
        
        return false;
    }

    public function reject(string $reason, int $userId = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_REJECTED;
        $this->verified_by = $userId ?? auth()->id();
        $this->verified_at = now();
        $this->rejection_reason = $reason;

        if ($this->save()) {
            ActivityLog::logActivity(
                'rejected',
                self::class,
                $this->id,
                ['rejected_by' => $this->verified_by, 'reason' => $reason]
            );
            
            return true;
        }
        
        return false;
    }

    public function markAsExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        $this->status = self::STATUS_EXPIRED;

        if ($this->save()) {
            ActivityLog::logActivity(
                'expired',
                self::class,
                $this->id,
                ['expiry_date' => $this->expiry_date]
            );
            
            return true;
        }
        
        return false;
    }

    public function resetToPending(): bool
    {
        if ($this->status === self::STATUS_PENDING) {
            return true;
        }

        $this->status = self::STATUS_PENDING;
        $this->verified_by = null;
        $this->verified_at = null;
        $this->rejection_reason = null;

        return $this->save();
    }

    public function updateExpiry(Carbon $newExpiryDate): bool
    {
        $oldExpiry = $this->expiry_date;
        $this->expiry_date = $newExpiryDate;
        
        // If document was expired and now has future expiry, reset to pending
        if ($this->status === self::STATUS_EXPIRED && $newExpiryDate->isFuture()) {
            $this->resetToPending();
        }

        if ($this->save()) {
            ActivityLog::logActivity(
                'expiry_updated',
                self::class,
                $this->id,
                ['old_expiry' => $oldExpiry, 'new_expiry' => $newExpiryDate]
            );
            
            return true;
        }
        
        return false;
    }

    public function checkAndUpdateExpiry(): bool
    {
        if ($this->is_expired && $this->status !== self::STATUS_EXPIRED) {
            return $this->markAsExpired();
        }
        
        return false;
    }

    public function getRequiredForBooking(): bool
    {
        if (!$this->booking) {
            return false;
        }

        $requiredDocs = $this->booking->getRequiredDocuments();
        return array_key_exists($this->document_type, $requiredDocs);
    }

    public function generateSecureFileName(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize the base name
        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $baseName = substr($baseName, 0, 50); // Limit length
        
        // Add timestamp and random string for uniqueness
        $timestamp = now()->format('YmdHis');
        $random = substr(md5(uniqid()), 0, 8);
        
        return "{$baseName}_{$timestamp}_{$random}.{$extension}";
    }

    public function getStoragePath(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        
        return "documents/{$year}/{$month}/{$this->customer_id}";
    }

    public static function getTypeRequirements(string $type): array
    {
        $requirements = [
            self::TYPE_PASSPORT => [
                'expiry_required' => true,
                'max_age_months' => 120, // 10 years
                'description' => 'Valid passport with at least 6 months remaining validity',
            ],
            self::TYPE_LICENSE => [
                'expiry_required' => true,
                'max_age_months' => 60, // 5 years
                'description' => 'Valid driving license',
            ],
            self::TYPE_INVOICE => [
                'expiry_required' => false,
                'max_age_months' => 12, // 1 year
                'description' => 'Original purchase invoice or receipt',
            ],
            self::TYPE_INSURANCE => [
                'expiry_required' => true,
                'max_age_months' => 12, // 1 year
                'description' => 'Valid insurance certificate',
            ],
            self::TYPE_CUSTOMS => [
                'expiry_required' => false,
                'max_age_months' => 6, // 6 months
                'description' => 'Customs declaration or clearance document',
            ],
            self::TYPE_OTHER => [
                'expiry_required' => false,
                'max_age_months' => 24, // 2 years
                'description' => 'Additional supporting document',
            ],
        ];

        return $requirements[$type] ?? $requirements[self::TYPE_OTHER];
    }

    public function isValidFileType(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_MIME_TYPES);
    }

    public function isValidFileSize(int $size): bool
    {
        return $size <= self::MAX_FILE_SIZE;
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'booking_id' => 'required|exists:bookings,id',
            'customer_id' => 'required|exists:customers,id',
            'document_type' => 'required|in:' . implode(',', self::VALID_TYPES),
            'file_name' => 'required|string|max:255',
            'file_path' => 'required|string|max:500',
            'file_size' => 'required|integer|min:1|max:' . self::MAX_FILE_SIZE,
            'mime_type' => 'required|string|in:' . implode(',', self::ALLOWED_MIME_TYPES),
            'status' => 'required|in:' . implode(',', self::VALID_STATUSES),
            'expiry_date' => 'nullable|date|after:today',
            'rejection_reason' => 'nullable|string|max:1000',
        ];
    }

    public static function uploadValidationRules(): array
    {
        $maxSizeMB = self::MAX_FILE_SIZE / 1048576;
        
        return [
            'file' => [
                'required',
                'file',
                "max:{$maxSizeMB}",
                'mimes:pdf,jpeg,jpg,png,gif,webp',
            ],
            'document_type' => 'required|in:' . implode(',', self::VALID_TYPES),
            'expiry_date' => 'nullable|date|after:today',
        ];
    }

    public static function updateValidationRules(): array
    {
        $rules = self::validationRules();
        
        // Make most fields optional for updates
        $rules['booking_id'] = 'sometimes|' . $rules['booking_id'];
        $rules['customer_id'] = 'sometimes|' . $rules['customer_id'];
        $rules['document_type'] = 'sometimes|' . $rules['document_type'];
        $rules['file_name'] = 'sometimes|' . $rules['file_name'];
        $rules['file_path'] = 'sometimes|' . $rules['file_path'];
        $rules['file_size'] = 'sometimes|' . $rules['file_size'];
        $rules['mime_type'] = 'sometimes|' . $rules['mime_type'];
        $rules['status'] = 'sometimes|' . $rules['status'];
        
        return $rules;
    }
}