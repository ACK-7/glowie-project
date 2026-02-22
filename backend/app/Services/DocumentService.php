<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\ActivityLog;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;
use Exception;

/**
 * Document Service
 * 
 * Handles all business logic related to document management including
 * verification workflows, automated expiry detection, missing document
 * identification, and secure file handling.
 */
class DocumentService
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private NotificationService $notificationService
    ) {}

    /**
     * Upload and create a new document with comprehensive validation
     */
    public function uploadDocument(UploadedFile $file, array $data): Document
    {
        DB::beginTransaction();
        
        try {
            // Validate file and data
            $this->validateDocumentUpload($file, $data);
            
            // Create document instance for path generation
            $document = new Document();
            $document->fill($data);
            
            // Generate secure file name and path
            $fileName = $document->generateSecureFileName($file->getClientOriginalName());
            $storagePath = $document->getStoragePath();
            $filePath = $storagePath . '/' . $fileName;
            
            // Store the file
            $storedPath = $file->storeAs($storagePath, $fileName, 'public');
            
            if (!$storedPath) {
                throw new Exception('Failed to store uploaded file');
            }
            
            // Prepare document data
            $documentData = array_merge($data, [
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $storedPath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => Document::STATUS_PENDING,
            ]);
            
            // Create document record
            $document = $this->documentRepository->create($documentData);
            
            // Send notification for document upload
            $this->notificationService->sendDocumentUploadedNotification($document);
            
            // Create audit trail
            $this->createAuditTrail('document_uploaded', $document, [
                'action' => 'Document uploaded',
                'user_id' => auth()->id(),
                'file_name' => $document->file_name,
                'file_size' => $document->file_size,
                'document_type' => $document->document_type
            ]);
            
            DB::commit();
            
            Log::info('Document uploaded successfully', [
                'document_id' => $document->id,
                'booking_id' => $document->booking_id,
                'customer_id' => $document->customer_id,
                'document_type' => $document->document_type,
                'file_size' => $document->file_size
            ]);
            
            return $document;
            
        } catch (Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded file if it exists
            if (isset($storedPath) && Storage::disk('public')->exists($storedPath)) {
                Storage::disk('public')->delete($storedPath);
            }
            
            Log::error('Failed to upload document', [
                'error' => $e->getMessage(),
                'data' => $data,
                'file_name' => $file->getClientOriginalName()
            ]);
            throw $e;
        }
    }

    /**
     * Approve a document with verification workflow
     */
    public function approveDocument(int $documentId, string $notes = null): Document
    {
        DB::beginTransaction();
        
        try {
            $document = $this->documentRepository->findOrFail($documentId);
            
            // Validate document can be approved
            if ($document->status !== Document::STATUS_PENDING) {
                throw new Exception("Document cannot be approved from {$document->status} status");
            }
            
            // Approve the document
            if (!$document->approve(auth()->id(), $notes)) {
                throw new Exception('Failed to approve document');
            }
            
            // Send approval notification
            $this->notificationService->sendDocumentApprovedNotification($document, $notes);
            
            // Check if all required documents are now complete for the booking
            $this->checkBookingDocumentCompleteness($document->booking_id);
            
            // Create audit trail
            $this->createAuditTrail('document_approved', $document, [
                'approved_by' => auth()->id(),
                'notes' => $notes,
                'approved_at' => now()
            ]);
            
            DB::commit();
            
            Log::info('Document approved successfully', [
                'document_id' => $document->id,
                'approved_by' => auth()->id(),
                'booking_id' => $document->booking_id
            ]);
            
            return $document->fresh();
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve document', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Reject a document with reason
     */
    public function rejectDocument(int $documentId, string $reason): Document
    {
        DB::beginTransaction();
        
        try {
            $document = $this->documentRepository->findOrFail($documentId);
            
            // Validate document can be rejected
            if ($document->status !== Document::STATUS_PENDING) {
                throw new Exception("Document cannot be rejected from {$document->status} status");
            }
            
            // Reject the document
            if (!$document->reject($reason, auth()->id())) {
                throw new Exception('Failed to reject document');
            }
            
            // Send rejection notification
            $this->notificationService->sendDocumentRejectedNotification($document, $reason);
            
            // Create audit trail
            $this->createAuditTrail('document_rejected', $document, [
                'rejected_by' => auth()->id(),
                'reason' => $reason,
                'rejected_at' => now()
            ]);
            
            DB::commit();
            
            Log::info('Document rejected', [
                'document_id' => $document->id,
                'rejected_by' => auth()->id(),
                'reason' => $reason
            ]);
            
            return $document->fresh();
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject document', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update document expiry date
     */
    public function updateDocumentExpiry(int $documentId, Carbon $newExpiryDate): Document
    {
        DB::beginTransaction();
        
        try {
            $document = $this->documentRepository->findOrFail($documentId);
            $oldExpiry = $document->expiry_date;
            
            // Validate new expiry date
            if ($newExpiryDate->isPast()) {
                throw new Exception('Expiry date cannot be in the past');
            }
            
            // Update expiry
            if (!$document->updateExpiry($newExpiryDate)) {
                throw new Exception('Failed to update document expiry');
            }
            
            // Send notification if significant change
            if ($oldExpiry && $oldExpiry->diffInDays($newExpiryDate) > 7) {
                $this->notificationService->sendDocumentExpiryUpdatedNotification($document, $oldExpiry, $newExpiryDate);
            }
            
            // Create audit trail
            $this->createAuditTrail('expiry_updated', $document, [
                'old_expiry' => $oldExpiry,
                'new_expiry' => $newExpiryDate,
                'updated_by' => auth()->id()
            ]);
            
            DB::commit();
            
            Log::info('Document expiry updated', [
                'document_id' => $document->id,
                'old_expiry' => $oldExpiry,
                'new_expiry' => $newExpiryDate
            ]);
            
            return $document->fresh();
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update document expiry', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete a document with file cleanup
     */
    public function deleteDocument(int $documentId, string $reason): bool
    {
        DB::beginTransaction();
        
        try {
            $document = $this->documentRepository->findOrFail($documentId);
            
            // Store document data for audit
            $documentData = $document->toArray();
            
            // Create audit trail before deletion
            $this->createAuditTrail('document_deleted', $document, [
                'reason' => $reason,
                'deleted_by' => auth()->id(),
                'document_data' => $documentData
            ]);
            
            // Send notification
            $this->notificationService->sendDocumentDeletedNotification($document, $reason);
            
            // Delete the document (file cleanup handled by model event)
            $deleted = $this->documentRepository->delete($documentId);
            
            DB::commit();
            
            Log::info('Document deleted successfully', [
                'document_id' => $documentId,
                'file_name' => $documentData['file_name'],
                'reason' => $reason
            ]);
            
            return $deleted;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete document', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get documents requiring verification
     */
    public function getDocumentsRequiringVerification(): Collection
    {
        return $this->documentRepository->getRequiringVerification();
    }

    /**
     * Get documents expiring soon
     */
    public function getDocumentsExpiringSoon(int $days = 30): Collection
    {
        return $this->documentRepository->getExpiringWithin($days);
    }

    /**
     * Get expired documents
     */
    public function getExpiredDocuments(): Collection
    {
        return $this->documentRepository->getExpired();
    }

    /**
     * Detect and process expired documents
     */
    public function processExpiredDocuments(): array
    {
        $results = [
            'processed' => 0,
            'notifications_sent' => 0,
            'errors' => []
        ];
        
        try {
            // Get documents that should be expired but aren't marked as such
            $documentsToExpire = Document::where('expiry_date', '<', now())
                ->where('status', '!=', Document::STATUS_EXPIRED)
                ->get();
            
            foreach ($documentsToExpire as $document) {
                try {
                    DB::beginTransaction();
                    
                    if ($document->markAsExpired()) {
                        $results['processed']++;
                        
                        // Send expiry notification
                        $this->notificationService->sendDocumentExpiredNotification($document);
                        $results['notifications_sent']++;
                        
                        // Create audit trail
                        $this->createAuditTrail('document_expired', $document, [
                            'expired_at' => now(),
                            'original_expiry' => $document->expiry_date,
                            'auto_processed' => true
                        ]);
                    }
                    
                    DB::commit();
                    
                } catch (Exception $e) {
                    DB::rollBack();
                    $results['errors'][] = [
                        'document_id' => $document->id,
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Failed to process expired document', [
                        'document_id' => $document->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            Log::info('Expired documents processed', $results);
            
        } catch (Exception $e) {
            Log::error('Failed to process expired documents', [
                'error' => $e->getMessage()
            ]);
            $results['errors'][] = ['general' => $e->getMessage()];
        }
        
        return $results;
    }

    /**
     * Detect missing documents for a booking
     */
    public function detectMissingDocuments(int $bookingId): array
    {
        try {
            $booking = Booking::with(['documents', 'route', 'vehicle'])->findOrFail($bookingId);
            
            // Get required documents based on booking details
            $requiredDocuments = $this->getRequiredDocumentsForBooking($booking);
            
            // Get uploaded documents
            $uploadedDocuments = $booking->documents->pluck('document_type')->toArray();
            
            // Find missing documents
            $missingDocuments = [];
            foreach ($requiredDocuments as $type => $requirements) {
                if (!in_array($type, $uploadedDocuments)) {
                    $missingDocuments[$type] = $requirements;
                }
            }
            
            return $missingDocuments;
            
        } catch (Exception $e) {
            Log::error('Failed to detect missing documents', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send missing document requests to customer
     */
    public function sendMissingDocumentRequests(int $bookingId): bool
    {
        try {
            $missingDocuments = $this->detectMissingDocuments($bookingId);
            
            if (empty($missingDocuments)) {
                return true; // No missing documents
            }
            
            $booking = Booking::findOrFail($bookingId);
            
            // Send notification
            $this->notificationService->sendMissingDocumentsNotification($booking, array_keys($missingDocuments));
            
            // Create audit trail
            $this->createAuditTrail('missing_documents_requested', null, [
                'booking_id' => $bookingId,
                'missing_documents' => array_keys($missingDocuments),
                'requested_by' => auth()->id(),
                'requested_at' => now()
            ]);
            
            Log::info('Missing document requests sent', [
                'booking_id' => $bookingId,
                'missing_documents' => array_keys($missingDocuments)
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to send missing document requests', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get document statistics
     */
    public function getDocumentStatistics(): array
    {
        return $this->documentRepository->getDocumentStatistics();
    }

    /**
     * Search documents
     */
    public function searchDocuments(string $query): Collection
    {
        return $this->documentRepository->searchDocuments($query);
    }

    /**
     * Get documents by filters
     */
    public function getDocumentsWithFilters(array $filters): Collection
    {
        return $this->documentRepository->getFilteredPaginated($filters);
    }

    /**
     * Validate document upload
     */
    private function validateDocumentUpload(UploadedFile $file, array $data): void
    {
        // Validate file type
        if (!in_array($file->getMimeType(), Document::ALLOWED_MIME_TYPES)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', Document::ALLOWED_MIME_TYPES));
        }
        
        // Validate file size
        if ($file->getSize() > Document::MAX_FILE_SIZE) {
            $maxSizeMB = Document::MAX_FILE_SIZE / 1048576;
            throw new Exception("File size exceeds maximum allowed size of {$maxSizeMB}MB");
        }
        
        // Validate document type
        if (!in_array($data['document_type'], Document::VALID_TYPES)) {
            throw new Exception('Invalid document type');
        }
        
        // Validate booking exists
        $booking = Booking::find($data['booking_id']);
        if (!$booking) {
            throw new Exception('Invalid booking ID');
        }
        
        // Validate customer exists
        $customer = Customer::find($data['customer_id']);
        if (!$customer) {
            throw new Exception('Invalid customer ID');
        }
        
        // Check if document type already exists for this booking
        $existingDocument = Document::where('booking_id', $data['booking_id'])
            ->where('document_type', $data['document_type'])
            ->where('status', '!=', Document::STATUS_REJECTED)
            ->first();
            
        if ($existingDocument) {
            throw new Exception("Document of type {$data['document_type']} already exists for this booking");
        }
    }

    /**
     * Get required documents for a booking
     */
    private function getRequiredDocumentsForBooking(Booking $booking): array
    {
        $requiredDocuments = [
            Document::TYPE_PASSPORT => Document::getTypeRequirements(Document::TYPE_PASSPORT),
            Document::TYPE_LICENSE => Document::getTypeRequirements(Document::TYPE_LICENSE),
            Document::TYPE_INVOICE => Document::getTypeRequirements(Document::TYPE_INVOICE),
        ];
        
        // Add route-specific requirements
        if ($booking->route) {
            $routeOrigin = strtolower($booking->route->origin_country ?? '');
            
            // Add insurance for international routes
            if ($routeOrigin !== 'uganda') {
                $requiredDocuments[Document::TYPE_INSURANCE] = Document::getTypeRequirements(Document::TYPE_INSURANCE);
            }
            
            // Add customs documents for certain countries
            if (in_array($routeOrigin, ['japan', 'uk', 'uae'])) {
                $requiredDocuments[Document::TYPE_CUSTOMS] = Document::getTypeRequirements(Document::TYPE_CUSTOMS);
            }
        }
        
        return $requiredDocuments;
    }

    /**
     * Check if all required documents are complete for a booking
     */
    private function checkBookingDocumentCompleteness(int $bookingId): void
    {
        try {
            $missingDocuments = $this->detectMissingDocuments($bookingId);
            
            if (empty($missingDocuments)) {
                $booking = Booking::findOrFail($bookingId);
                
                // All documents complete - send notification
                $this->notificationService->sendDocumentsCompleteNotification($booking);
                
                // Create audit trail
                $this->createAuditTrail('documents_complete', null, [
                    'booking_id' => $bookingId,
                    'completed_at' => now()
                ]);
                
                Log::info('All required documents complete for booking', [
                    'booking_id' => $bookingId
                ]);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to check booking document completeness', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create audit trail entry
     */
    private function createAuditTrail(string $action, ?Document $document, array $details): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $document ? Document::class : null,
            'model_id' => $document?->id,
            'changes' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}