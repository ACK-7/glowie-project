<?php

namespace App\Repositories;

use App\Models\Document;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Document Repository Implementation
 */
class DocumentRepository extends BaseRepository implements DocumentRepositoryInterface
{
    public function __construct(Document $model)
    {
        parent::__construct($model);
    }

    /**
     * Get documents by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->byStatus($status)->get();
    }

    /**
     * Get pending documents
     */
    public function getPending(): Collection
    {
        return $this->model->pending()->get();
    }

    /**
     * Get approved documents
     */
    public function getApproved(): Collection
    {
        return $this->model->approved()->get();
    }

    /**
     * Get rejected documents
     */
    public function getRejected(): Collection
    {
        return $this->model->rejected()->get();
    }

    /**
     * Get expired documents
     */
    public function getExpired(): Collection
    {
        return $this->model->expired()->get();
    }

    /**
     * Get documents by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->byType($type)->get();
    }

    /**
     * Get documents by customer
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->model->byCustomer($customerId)->get();
    }

    /**
     * Get documents by booking
     */
    public function getByBooking(int $bookingId): Collection
    {
        return $this->model->byBooking($bookingId)->get();
    }

    /**
     * Get documents expiring within days
     */
    public function getExpiringWithin(int $days = 30): Collection
    {
        return $this->model->expiringWithin($days)->get();
    }

    /**
     * Get documents requiring verification
     */
    public function getRequiringVerification(): Collection
    {
        return $this->model
            ->with(['booking.customer', 'customer'])
            ->where('status', Document::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get document statistics
     */
    public function getDocumentStatistics(): array
    {
        $totalDocuments = $this->model->count();
        
        return [
            'total_documents' => $totalDocuments,
            'pending_documents' => $this->model->where('status', Document::STATUS_PENDING)->count(),
            'approved_documents' => $this->model->where('status', Document::STATUS_APPROVED)->count(),
            'rejected_documents' => $this->model->where('status', Document::STATUS_REJECTED)->count(),
            'expired_documents' => $this->model->where('status', Document::STATUS_EXPIRED)->count(),
            'expiring_soon' => $this->model->expiringWithin(30)->count(),
            'type_breakdown' => $this->model
                ->select('document_type', DB::raw('COUNT(*) as count'))
                ->groupBy('document_type')
                ->pluck('count', 'document_type')
                ->toArray(),
            'verification_rate' => $totalDocuments > 0 ? 
                ($this->model->where('status', Document::STATUS_APPROVED)->count() / $totalDocuments) * 100 : 0,
            'rejection_rate' => $totalDocuments > 0 ? 
                ($this->model->where('status', Document::STATUS_REJECTED)->count() / $totalDocuments) * 100 : 0,
            'average_file_size' => $this->model->avg('file_size'),
            'total_storage_used' => $this->model->sum('file_size'),
        ];
    }

    /**
     * Search documents
     */
    public function searchDocuments(string $query): Collection
    {
        return $this->model
            ->with(['booking.customer', 'customer'])
            ->where(function ($q) use ($query) {
                $q->where('file_name', 'LIKE', "%{$query}%")
                  ->orWhere('document_type', 'LIKE', "%{$query}%")
                  ->orWhereHas('booking', function ($bookingQuery) use ($query) {
                      $bookingQuery->where('booking_reference', 'LIKE', "%{$query}%");
                  })
                  ->orWhereHas('customer', function ($customerQuery) use ($query) {
                      $customerQuery->where('first_name', 'LIKE', "%{$query}%")
                               ->orWhere('last_name', 'LIKE', "%{$query}%")
                               ->orWhere('email', 'LIKE', "%{$query}%");
                  });
            })
            ->get();
    }

    /**
     * Get recent documents
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model
            ->with(['booking.customer', 'customer'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get missing documents by booking
     */
    public function getMissingDocumentsByBooking(int $bookingId): array
    {
        $booking = \App\Models\Booking::with(['documents', 'route'])->find($bookingId);
        
        if (!$booking) {
            return [];
        }

        $requiredDocuments = $booking->getRequiredDocuments();
        $uploadedDocuments = $booking->documents->pluck('document_type')->toArray();
        
        return array_diff_key($requiredDocuments, array_flip($uploadedDocuments));
    }

    /**
     * Apply search filter for documents
     */
    protected function applySearchFilter(Builder $query, string $searchTerm): void
    {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('file_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('document_type', 'LIKE', "%{$searchTerm}%")
              ->orWhereHas('booking', function ($bookingQuery) use ($searchTerm) {
                  $bookingQuery->where('booking_reference', 'LIKE', "%{$searchTerm}%");
              })
              ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                  $customerQuery->where('first_name', 'LIKE', "%{$searchTerm}%")
                               ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
              });
        });
    }

    /**
     * Apply custom filters for documents
     */
    protected function applyCustomFilter(Builder $query, string $key, $value): void
    {
        switch ($key) {
            case 'document_type':
                $query->where('document_type', $value);
                break;
                
            case 'customer_id':
                $query->where('customer_id', $value);
                break;
                
            case 'booking_id':
                $query->where('booking_id', $value);
                break;
                
            case 'expiring_soon':
                if ($value) {
                    $query->where('expiry_date', '<=', now()->addDays(30))
                          ->where('expiry_date', '>', now())
                          ->where('status', Document::STATUS_APPROVED);
                }
                break;
                
            case 'verified_by':
                $query->where('verified_by', $value);
                break;
                
            case 'file_size_min':
                $query->where('file_size', '>=', $value);
                break;
                
            case 'file_size_max':
                $query->where('file_size', '<=', $value);
                break;
                
            case 'mime_type':
                $query->where('mime_type', $value);
                break;
        }
    }
}